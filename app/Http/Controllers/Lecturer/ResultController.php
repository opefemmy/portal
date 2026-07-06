<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssignment;
use App\Models\StudentCourse;
use App\Models\Result;
use App\Models\Student;
use App\Models\Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ResultController extends Controller
{
    /**
     * Show students registered for lecturer's assigned course
     */
    public function courseStudents(Course $course)
    {
        // Verify lecturer is assigned to this course
        $assignment = CourseAssignment::where('course_id', $course->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        $currentSession = Session::getCurrentSession();

        // Get students registered for this course
        $studentCourses = StudentCourse::where('course_id', $course->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with(['student.user', 'student.department', 'student.programme', 'results'])
            ->get();

        // Get existing results to show status
        $results = Result::whereIn('student_course_id', $studentCourses->pluck('id'))
            ->get()
            ->keyBy('student_course_id');

        return view('lecturer.course-students', compact('course', 'studentCourses', 'results', 'assignment'));
    }

    /**
     * Show result entry form
     */
    public function enter(Course $course)
    {
        // Verify lecturer is assigned to this course
        $assignment = CourseAssignment::where('course_id', $course->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        $currentSession = Session::getCurrentSession();

        // Get students registered for this course
        $studentCourses = StudentCourse::where('course_id', $course->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with(['student.user', 'results'])
            ->get();

        // Get existing results
        $existingResults = Result::whereIn('student_course_id', $studentCourses->pluck('id'))
            ->get()
            ->keyBy('student_course_id');

        return view('lecturer.results-enter', compact('course', 'studentCourses', 'existingResults', 'assignment'));
    }

    /**
     * Store results manually entered
     */
    public function store(Request $request, Course $course)
    {
        $assignment = CourseAssignment::where('course_id', $course->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        $request->validate([
            'results' => 'required|array',
            'results.*.student_course_id' => 'required|exists:student_courses,id',
            'results.*.ca1' => 'nullable|numeric|min:0|max:40',
            'results.*.ca2' => 'nullable|numeric|min:0|max:40',
            'results.*.exam' => 'nullable|numeric|min:0|max:60',
        ]);

        $currentSession = Session::getCurrentSession();

        foreach ($request->results as $resultData) {
            $studentCourseId = $resultData['student_course_id'];
            $ca1 = $resultData['ca1'] ?? 0;
            $ca2 = $resultData['ca2'] ?? 0;
            $exam = $resultData['exam'] ?? 0;
            $total = $ca1 + $ca2 + $exam;

            // Calculate grade
            $grade = \App\Models\Grade::getGrade($total);

            // Check if result already exists
            $result = Result::updateOrCreate(
                ['student_course_id' => $studentCourseId],
                [
                    'ca1' => $ca1,
                    'ca2' => $ca2,
                    'exam' => $exam,
                    'total_score' => $total,
                    'grade' => $grade ? $grade->grade : null,
                    'grade_point' => $grade ? $grade->grade_point : 0,
                    'remarks' => $grade ? $grade->remark : null,
                    'status' => 'pending_approval',
                    'course_id' => $course->id,
                ]
            );

            // Calculate GPA/CGPA for the student
            $studentCourse = StudentCourse::find($studentCourseId);
            if ($studentCourse) {
                $result->calculateAll($studentCourse->student_id);
            }
        }

        return back()->with('success', 'Results saved successfully!');
    }

    /**
     * Edit a specific result before HOD approval
     */
    public function edit(Result $result)
    {
        // Verify lecturer owns this result
        $studentCourse = $result->studentCourse;
        $assignment = CourseAssignment::where('course_id', $studentCourse->course_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        // Check if result is approved
        if ($result->status === 'approved') {
            return back()->with('error', 'Cannot edit approved results.');
        }

        return view('lecturer.result-edit', compact('result', 'studentCourse'));
    }

    /**
     * Update a result
     */
    public function update(Request $request, Result $result)
    {
        $studentCourse = $result->studentCourse;
        $assignment = CourseAssignment::where('course_id', $studentCourse->course_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        if ($result->status === 'approved') {
            return back()->with('error', 'Cannot edit approved results.');
        }

        $request->validate([
            'ca1' => 'nullable|numeric|min:0|max:40',
            'ca2' => 'nullable|numeric|min:0|max:40',
            'exam' => 'nullable|numeric|min:0|max:60',
        ]);

        $ca1 = $request->ca1 ?? 0;
        $ca2 = $request->ca2 ?? 0;
        $exam = $request->exam ?? 0;
        $total = $ca1 + $ca2 + $exam;

        $grade = \App\Models\Grade::getGrade($total);

        $result->update([
            'ca1' => $ca1,
            'ca2' => $ca2,
            'exam' => $exam,
            'total_score' => $total,
            'grade' => $grade ? $grade->grade : null,
            'grade_point' => $grade ? $grade->grade_point : 0,
            'remarks' => $grade ? $grade->remark : null,
            'status' => 'pending_approval',
        ]);

        return redirect()->route('lecturer.courses.results', $studentCourse->course_id)
            ->with('success', 'Result updated successfully!');
    }

    /**
     * Bulk upload results via Excel
     */
    public function bulkUpload(Request $request, Course $course)
    {
        $assignment = CourseAssignment::where('course_id', $course->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$assignment) {
            return back()->with('error', 'You are not assigned to this course.');
        }

        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $file = $request->file('excel_file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Skip header row
        array_shift($rows);

        $currentSession = Session::getCurrentSession();
        $errors = [];
        $successCount = 0;

        foreach ($rows as $index => $row) {
            if (empty($row[0])) continue;

            // Expected format: Matric No, Fullname, CA1, CA2, Total (optional)
            $matricNo = trim($row[0]);
            $fullname = trim($row[1]) ?? '';
            $ca1 = floatval($row[2]) ?? 0;
            $ca2 = floatval($row[3]) ?? 0;
            $exam = floatval($row[4]) ?? 0;

            // Find student by matric number
            $student = Student::where('matric_number', $matricNo)->first();

            if (!$student) {
                $errors[] = "Row " . ($index + 2) . ": Student with matric number {$matricNo} not found.";
                continue;
            }

            // Find student's course registration
            $studentCourse = StudentCourse::where('student_id', $student->id)
                ->where('course_id', $course->id)
                ->where('session_id', $currentSession->id ?? 0)
                ->where('status', 'registered')
                ->first();

            if (!$studentCourse) {
                $errors[] = "Row " . ($index + 2) . ": {$matricNo} is not registered for this course.";
                continue;
            }

            $total = $ca1 + $ca2 + $exam;
            $grade = \App\Models\Grade::getGrade($total);

            Result::updateOrCreate(
                ['student_course_id' => $studentCourse->id],
                [
                    'ca1' => $ca1,
                    'ca2' => $ca2,
                    'exam' => $exam,
                    'total_score' => $total,
                    'grade' => $grade ? $grade->grade : null,
                    'grade_point' => $grade ? $grade->grade_point : 0,
                    'remarks' => $grade ? $grade->remark : null,
                    'status' => 'pending_approval',
                    'course_id' => $course->id,
                ]
            );

            $successCount++;
        }

        if (count($errors) > 0) {
            return back()->with('warning', "Uploaded {$successCount} results. " . count($errors) . " errors: " . implode(', ', array_slice($errors, 0, 5)));
        }

        return back()->with('success', "Successfully uploaded {$successCount} results!");
    }

    /**
     * Download result template
     */
    public function downloadTemplate(Course $course)
    {
        $currentSession = Session::getCurrentSession();

        // Get registered students
        $studentCourses = StudentCourse::where('course_id', $course->id)
            ->where('session_id', $currentSession->id ?? 0)
            ->where('status', 'registered')
            ->with('student.user')
            ->get();

        // Create spreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $sheet->setCellValue('A1', 'Matric No');
        $sheet->setCellValue('B1', 'Fullname');
        $sheet->setCellValue('C1', 'CA1');
        $sheet->setCellValue('D1', 'CA2');
        $sheet->setCellValue('E1', 'Exam');
        $sheet->setCellValue('F1', 'Total');

        // Data
        $row = 2;
        foreach ($studentCourses as $sc) {
            $sheet->setCellValue('A' . $row, $sc->student->matric_number);
            $sheet->setCellValue('B' . $row, $sc->student->user->name);
            $sheet->setCellValue('C' . $row, '');
            $sheet->setCellValue('D' . $row, '');
            $sheet->setCellValue('E' . $row, '');
            $sheet->setCellValue('F' . $row, '');
            $row++;
        }

        // Download
        $filename = $course->code . '_results_template.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}