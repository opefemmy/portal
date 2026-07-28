<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    public function index()
    {
        $courses = Course::with('school', 'department', 'programme')->get();
        return view('admin.courses.index', compact('courses'));
    }

    /**
     * Show upload form
     */
    public function uploadForm()
    {
        return view('admin.courses.upload');
    }

    /**
     * Process course upload from Excel/CSV
     */
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:2048',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        // Read the file
        if ($extension === 'csv') {
            $data = array_map('str_getcsv', file($file->getRealPath()));
        } else {
            // For Excel files, use simple XML reading (works with .xlsx)
            $data = $this->readExcel($file->getRealPath());
        }

        if (empty($data) || count($data) < 2) {
            return back()->with('error', 'File is empty or has no data rows.');
        }

        // Get headers (first row)
        $headers = array_map('strtolower', array_map('trim', $data[0]));
        unset($data[0]);

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($data as $rowIndex => $row) {
            if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                continue;
            }

            // Map row data to headers
            $rowData = [];
            foreach ($headers as $index => $header) {
                $rowData[$header] = isset($row[$index]) ? trim($row[$index]) : null;
            }

            try {
                // Validate required fields
                if (empty($rowData['code']) || empty($rowData['title']) ||
                    empty($rowData['units']) || empty($rowData['semester']) ||
                    empty($rowData['school_code']) || empty($rowData['department_code']) ||
                    empty($rowData['programme_code']) || empty($rowData['level'])) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": Missing required fields";
                    continue;
                }

                // Find school by code or name
                $school = School::where('code', $rowData['school_code'])
                    ->orWhere('name', 'like', '%' . $rowData['school_code'] . '%')
                    ->first();
                if (!$school) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": School not found - " . $rowData['school_code'];
                    continue;
                }

                // Find department by code or name
                $department = Department::where('code', $rowData['department_code'])
                    ->orWhere('name', 'like', '%' . $rowData['department_code'] . '%')
                    ->first();
                if (!$department) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": Department not found - " . $rowData['department_code'];
                    continue;
                }

                // Find programme by code or name
                $programme = Programme::where('code', $rowData['programme_code'])
                    ->orWhere('name', 'like', '%' . $rowData['programme_code'] . '%')
                    ->first();
                if (!$programme) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": Programme not found - " . $rowData['programme_code'];
                    continue;
                }

                // Validate semester
                $semester = strtolower($rowData['semester']);
                if (!in_array($semester, ['first', 'second'])) {
                    $errorCount++;
                    $errors[] = "Row " . ($rowIndex + 2) . ": Invalid semester - " . $rowData['semester'];
                    continue;
                }

                // Check if course already exists
                $existingCourse = Course::where('code', $rowData['code'])
                    ->where('school_id', $school->id)
                    ->where('department_id', $department->id)
                    ->where('programme_id', $programme->id)
                    ->where('level', $rowData['level'])
                    ->first();

                if ($existingCourse) {
                    // Update existing course
                    $existingCourse->update([
                        'title' => $rowData['title'],
                        'units' => $rowData['units'],
                        'semester' => $semester,
                        'description' => $rowData['description'] ?? null,
                    ]);
                } else {
                    // Create new course
                    Course::create([
                        'code' => strtoupper($rowData['code']),
                        'title' => $rowData['title'],
                        'units' => $rowData['units'],
                        'semester' => $semester,
                        'school_id' => $school->id,
                        'department_id' => $department->id,
                        'programme_id' => $programme->id,
                        'level' => $rowData['level'],
                        'description' => $rowData['description'] ?? null,
                    ]);
                }

                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
                Log::error("Course upload error row " . ($rowIndex + 2) . ": " . $e->getMessage());
            }
        }

        if ($errorCount > 0) {
            $message = "Upload completed: $successCount courses processed successfully. $errorCount errors.";
            if (count($errors) <= 10) {
                return back()->with('warning', $message)->with('errors', $errors);
            }
            return back()->with('warning', $message);
        }

        return redirect()->route('admin.courses.index')
            ->with('success', "$successCount courses uploaded successfully!");
    }

    /**
     * Read Excel file (basic support for .xlsx files)
     */
    private function readExcel($filePath)
    {
        $data = [];
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            $sheet = simplexml_load_string($zip->getFromName('xl/sharedStrings.xml'));
            $zip->close();

            if ($sheet) {
                $strings = [];
                foreach ($sheet->si as $string) {
                    $strings[] = (string) $string->t;
                }

                $zip = new \ZipArchive();
                $zip->open($filePath);
                $sheetData = $zip->getFromName('xl/worksheets/sheet1.xml');
                $zip->close();

                $xml = simplexml_load_string($sheetData);
                $namespaces = $xml->getNamespaces(true);

                foreach ($xml->sheetData->row as $row) {
                    $rowData = [];
                    foreach ($row->c as $cell) {
                        $cellRef = (string) $cell['r'];
                        $col = preg_replace('/[0-9]/', '', $cellRef);
                        $colIndex = $this->columnLetterToIndex($col);

                        if (isset($cell->v)) {
                            $value = (string) $cell->v;
                            // Check if it's a shared string
                            if (isset($cell['t']) && (string) $cell['t'] === 's') {
                                $value = $strings[$value] ?? '';
                            }
                            $rowData[$colIndex] = $value;
                        } else {
                            $rowData[$colIndex] = '';
                        }
                    }
                    if (!empty($rowData)) {
                        $data[] = $rowData;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Convert column letter to index (A=0, B=1, etc.)
     */
    private function columnLetterToIndex($letter)
    {
        $index = 0;
        $length = strlen($letter);
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord(strtoupper($letter[$i])) - ord('A') + 1);
        }
        return $index - 1;
    }

    public function create()
    {
        $data = [
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
        ];
        return view('admin.courses.create', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'title' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:10',
            'semester' => 'required|in:first,second',
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
        ]);

        // Check unique constraint: school + dept + prog + level + code
        $exists = Course::where('code', $validated['code'])
            ->where('school_id', $validated['school_id'])
            ->where('department_id', $validated['department_id'])
            ->where('programme_id', $validated['programme_id'])
            ->where('level', $validated['level'])
            ->exists();

        if ($exists) {
            return back()->with('error', 'This course already exists for the selected criteria.');
        }

        Course::create($validated);
        return redirect()->route('admin.courses.index')->with('success', 'Course created');
    }

    public function edit(Course $course)
    {
        $data = [
            'course' => $course,
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
        ];
        return view('admin.courses.edit', $data);
    }

    public function update(Request $request, Course $course)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'title' => 'required|string|max:255',
            'units' => 'required|integer|min:1|max:10',
            'semester' => 'required|in:first,second',
            'school_id' => 'required|exists:schools,id',
            'department_id' => 'required|exists:departments,id',
            'programme_id' => 'required|exists:programmes,id',
            'level' => 'required|integer|min:1|max:6',
            'description' => 'nullable|string',
        ]);

        $course->update($validated);
        return redirect()->route('admin.courses.index')->with('success', 'Course updated');
    }

    public function destroy(Course $course)
    {
        $course->delete();
        return back()->with('success', 'Course deleted');
    }
}