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
use App\Models\Nationality;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['user', 'school', 'department', 'programme', 'session'])->latest()->get();
        return view('admin.students.index', compact('students'));
    }

    public function show(Student $student)
    {
        $student->load(['user', 'school', 'department', 'programme', 'session']);
        return view('admin.students.show', compact('student'));
    }

    public function create()
    {
        $data = [
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
            'sessions' => Session::all(),
            'states' => State::all(),
            'lgas' => collect(),
            'nationalities' => Nationality::all(),
        ];
        return view('admin.students.create', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'matric_number' => 'required|unique:students',
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'level' => 'required|integer|min:1|max:6',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:local_governments,id',
            'nationality_id' => 'nullable|exists:nationalities,id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($request->input('password', 'password123')),
                'role_id' => 9, // Student role
                'is_active' => true,
            ]);

            Student::create([
                'user_id' => $user->id,
                'matric_number' => $validated['matric_number'],
                'school_id' => $validated['school_id'],
                'department_id' => $validated['department_id'],
                'programme_id' => $validated['programme_id'],
                'session_id' => $validated['session_id'],
                'level' => $validated['level'],
                'status' => 'active',
                'state_id' => $validated['state_id'] ?? null,
                'lga_id' => $validated['lga_id'] ?? null,
                'nationality_id' => $validated['nationality_id'] ?? null,
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Student created successfully');
    }

    public function edit(Student $student)
    {
        $data = [
            'student' => $student,
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
            'sessions' => Session::all(),
            'states' => State::all(),
            'lgas' => LocalGovernment::where('state_id', $student->state_id)->get(),
            'nationalities' => Nationality::all(),
        ];
        return view('admin.students.edit', $data);
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'matric_number' => ['required', Rule::unique('students')->ignore($student->id)],
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'session_id' => 'required|exists:sessions,id',
            'level' => 'required|integer|min:1|max:6',
            'status' => 'required|in:active,graduated,suspended,withdrawn',
            'state_id' => 'nullable|exists:states,id',
            'lga_id' => 'nullable|exists:local_governments,id',
            'nationality_id' => 'nullable|exists:nationalities,id',
        ]);

        $student->update($validated);

        return redirect()->route('admin.students.index')->with('success', 'Student updated successfully');
    }

    public function destroy(Student $student)
    {
        $student->user->delete();
        $student->delete();
        return back()->with('success', 'Student deleted successfully');
    }

    public function resetPassword(Student $student)
    {
        $newPassword = 'student123'; // Default password
        $student->user->update(['password' => Hash::make($newPassword)]);
        return back()->with('success', 'Password reset to default for ' . $student->matric_number);
    }

    public function getLGAs($stateId)
    {
        $lgas = LocalGovernment::where('state_id', $stateId)->get();
        return response()->json($lgas);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $count = 0;
        $errors = [];

        if ($extension === 'csv') {
            $data = array_map('str_getcsv', file($file));
            array_shift($data); // Remove header

            $studentRoleId = 9; // Student role ID

            foreach ($data as $row) {
                if (empty($row[0])) continue;

                try {
                    $name = $row[0] ?? 'Student';
                    $email = $row[1] ?? ($name . '@example.com');
                    $matricNumber = $row[2] ?? null;
                    $departmentId = $row[3] ?? null;
                    $programmeId = $row[4] ?? null;
                    $level = $row[5] ?? 1;
                    $sessionId = $row[6] ?? null;
                    $stateId = $row[7] ?? null;
                    $lgaId = $row[8] ?? null;
                    $nationalityId = $row[9] ?? 1;

                    if (!$matricNumber || !$departmentId || !$programmeId) {
                        $errors[] = "Missing required fields for: $name";
                        continue;
                    }

                    // Check if user already exists
                    if (User::where('email', $email)->exists()) {
                        $errors[] = "Email already exists: $email";
                        continue;
                    }

                    // Create user
                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password123'),
                        'role_id' => $studentRoleId,
                        'is_active' => true,
                    ]);

                    // Create student
                    Student::create([
                        'user_id' => $user->id,
                        'matric_number' => $matricNumber,
                        'department_id' => $departmentId,
                        'programme_id' => $programmeId,
                        'level' => $level,
                        'session_id' => $sessionId ?? 1,
                        'status' => 'active',
                        'state_id' => $stateId,
                        'lga_id' => $lgaId,
                        'nationality_id' => $nationalityId,
                    ]);

                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Error processing row: " . $e->getMessage();
                }
            }
        }

        $message = "$count students uploaded successfully";
        if (!empty($errors)) {
            $message .= ". Errors: " . implode('; ', array_slice($errors, 5));
        }

        return redirect()->route('admin.students.index')->with(empty($errors) ? 'success' : 'info', $message);
    }

    public function downloadTemplate()
    {
        $headers = ['name', 'email', 'matric_number', 'department_id', 'programme_id', 'level', 'state_id', 'lga_id', 'nationality_id'];
        $departments = Department::all()->pluck('id', 'code');
        $programmes = Programme::all()->pluck('id', 'code');

        $sampleData = [
            ['John Doe', 'john@example.com', '20240001', '1', '1', '1', '1', '1', '1'],
            ['Jane Smith', 'jane@example.com', '20240002', '2', '2', '2', '2', '2', '1'],
        ];

        $csv = "name,email,matric_number,department_id,programme_id,level,state_id,lga_id,nationality_id\n";
        foreach ($sampleData as $row) {
            $csv .= implode(',', $row) . "\n";
        }

        return response()->make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_upload_template.csv"',
        ]);
    }
}