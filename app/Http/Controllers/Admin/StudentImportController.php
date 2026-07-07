<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\State;
use App\Models\LocalGovernment;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class StudentImportController extends Controller
{
    public function index()
    {
        return view('admin.students.import');
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = fopen($request->file('csv_file')->getRealPath(), 'r');
        $headers = fgetcsv($file);

        // Normalize headers
        $headers = array_map(function($header) {
            return strtolower(trim(str_replace(' ', '_', $header)));
        }, $headers);

        // Map expected headers
        $headerMap = [
            'matricnumber' => 'matric_number',
            'matric_number' => 'matric_number',
            'firstname' => 'first_name',
            'first_name' => 'first_name',
            'middlename' => 'middle_name',
            'middle_name' => 'middle_name',
            'lastname' => 'last_name',
            'last_name' => 'last_name',
            'yearofentry' => 'year_of_entry',
            'year_of_entry' => 'year_of_entry',
            'school' => 'school',
            'department' => 'department',
            'programme' => 'programme',
            'level' => 'level',
            'stateoforigin' => 'state_of_origin',
            'state_of_origin' => 'state_of_origin',
            'lga' => 'lga',
            'state' => 'state_of_origin',
        ];

        $normalizedHeaders = [];
        foreach ($headers as $header) {
            $normalizedHeaders[] = $headerMap[$header] ?? $header;
        }

        $row = 1;
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            while (($data = fgetcsv($file)) !== false) {
                $row++;
                $record = array_combine($normalizedHeaders, $data);

                // Skip empty rows
                if (empty($record['matric_number']) || empty($record['first_name']) || empty($record['last_name'])) {
                    $errors[] = "Row $row: Missing required fields (matric_number, first_name, last_name)";
                    $errorCount++;
                    continue;
                }

                // Find or create school
                $school = null;
                if (!empty($record['school'])) {
                    $school = School::where('name', 'like', '%' . $record['school'] . '%')->first();
                }
                if (!$school) {
                    $school = School::first();
                }

                // Find or create department
                $department = null;
                if (!empty($record['department']) && $school) {
                    $department = Department::where('name', 'like', '%' . $record['department'] . '%')
                        ->where('school_id', $school->id)->first();
                }
                if (!$department && $school) {
                    $department = Department::where('school_id', $school->id)->first();
                }

                // Find or create programme
                $programme = null;
                if (!empty($record['programme'])) {
                    $programme = Programme::where('name', 'like', '%' . $record['programme'] . '%')->first();
                }
                if (!$programme) {
                    $programme = Programme::first();
                }

                // Find or create session
                $session = Session::where('is_current', true)->first();
                if (!$session) {
                    $session = Session::first();
                }

                // Find state and LGA
                $stateId = null;
                $lgaId = null;
                if (!empty($record['state_of_origin'])) {
                    $state = State::where('name', 'like', '%' . $record['state_of_origin'] . '%')->first();
                    if ($state) {
                        $stateId = $state->id;
                        if (!empty($record['lga'])) {
                            $lga = LocalGovernment::where('name', 'like', '%' . $record['lga'] . '%')
                                ->where('state_id', $state->id)->first();
                            if ($lga) {
                                $lgaId = $lga->id;
                            }
                        }
                    }
                }

                // Determine level
                $level = is_numeric($record['level'] ?? null) ? (int)$record['level'] : 1;

                // Create user - password is set to matric number, must change on first login
                $user = User::updateOrCreate(
                    ['email' => strtolower($record['matric_number']) . '@student.edu'],
                    [
                        'name' => trim($record['first_name'] . ' ' . ($record['middle_name'] ?? '') . ' ' . $record['last_name']),
                        'password' => Hash::make($record['matric_number']), // Default password is matric number
                        'role_id' => Role::where('slug', 'student')->first()?->id,
                        'gender' => 'male',
                        'is_active' => true,
                        'must_change_password' => true, // Force password change on first login
                        'matric_number' => $record['matric_number'],
                    ]
                );

                // Check if student already exists
                $student = Student::where('matric_number', $record['matric_number'])->first();

                if ($student) {
                    // Update existing student
                    $student->update([
                        'school_id' => $school?->id,
                        'department_id' => $department?->id,
                        'programme_id' => $programme?->id,
                        'session_id' => $session?->id,
                        'level' => $level,
                        'state_id' => $stateId,
                        'lga_id' => $lgaId,
                        'year_of_entry' => $record['year_of_entry'] ?? date('Y'),
                        'status' => 'active',
                    ]);
                } else {
                    // Create new student
                    Student::create([
                        'user_id' => $user->id,
                        'matric_number' => $record['matric_number'],
                        'school_id' => $school?->id,
                        'department_id' => $department?->id,
                        'programme_id' => $programme?->id,
                        'session_id' => $session?->id,
                        'level' => $level,
                        'state_id' => $stateId,
                        'lga_id' => $lgaId,
                        'year_of_entry' => $record['year_of_entry'] ?? date('Y'),
                        'status' => 'active',
                    ]);
                }

                $successCount++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $errors[] = "Error: " . $e->getMessage();
            $errorCount++;
        }

        fclose($file);

        return back()->with([
            'success' => "Import completed: $successCount students imported successfully.",
            'errors' => $errors,
            'errorCount' => $errorCount,
        ]);
    }

    public function downloadTemplate()
    {
        $headers = ['MatricNumber', 'FirstName', 'MiddleName', 'LastName', 'YearOfEntry', 'School', 'Department', 'Programme', 'Level', 'StateOfOrigin', 'LGA'];

        $filename = 'student_import_template.csv';
        $handle = fopen('php://temp', 'w');
        fputcsv($handle, $headers);

        // Add sample row
        fputcsv($handle, ['ND/2024/001', 'John', 'Doe', 'Smith', '2024', 'School of Computing', 'Computer Science', 'ND', '1', 'Lagos', ' Ikeja']);

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response()->download(
            $filename = tempnam(sys_get_temp_dir(), 'template'),
            $filename
        )->setContent($content);
    }
}