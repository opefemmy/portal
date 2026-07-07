<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\StudentCourse;
use App\Models\Course;
use App\Models\Student;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $query = Result::with(['studentCourse.student.user', 'studentCourse.course', 'approvedBy']);

        if ($request->session_id) {
            $query->whereHas('studentCourse', function($q) use ($request) {
                $q->where('session_id', $request->session_id);
            });
        }

        if ($request->semester) {
            $query->whereHas('studentCourse', function($q) use ($request) {
                $q->where('semester', $request->semester);
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $results = $query->latest()->paginate(20);
        return view('admin.results.index', compact('results'));
    }

    public function show(Result $result)
    {
        $result->load(['studentCourse.student.user', 'studentCourse.course', 'approvedBy']);
        return view('admin.results.show', compact('result'));
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:2048',
            'course_id' => 'required|exists:courses,id',
            'session_id' => 'required|exists:sessions,id',
            'semester' => 'required|in:first,second',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $count = 0;

        if (in_array($extension, ['csv', 'xlsx', 'xls'])) {
            // For CSV processing
            if ($extension === 'csv') {
                $data = array_map('str_getcsv', file($file));
                array_shift($data); // Remove header

                foreach ($data as $row) {
                    if (empty($row[0])) continue;

                    try {
                        $matricNumber = trim($row[0]);
                        $ca = floatval($row[1] ?? 0);
                        $test = floatval($row[2] ?? 0);
                        $exam = floatval($row[3] ?? 0);

                        $student = Student::where('matric_number', $matricNumber)->first();
                        if (!$student) continue;

                        $studentCourse = StudentCourse::where('student_id', $student->id)
                            ->where('course_id', $request->course_id)
                            ->where('session_id', $request->session_id)
                            ->where('semester', $request->semester)
                            ->first();

                        if (!$studentCourse) continue;

                        $totalScore = $ca + $test + $exam;

                        Result::updateOrCreate(
                            ['student_course_id' => $studentCourse->id],
                            [
                                'ca' => $ca,
                                'test' => $test,
                                'exam' => $exam,
                                'total_score' => $totalScore,
                                'status' => 'pending',
                            ]
                        );

                        $count++;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return back()->with('success', "$count results uploaded successfully");
    }

    public function downloadTemplate()
    {
        $headers = ['matric_number', 'ca_score', 'test_score', 'exam_score'];
        $csv = implode(',', $headers) . "\n";
        $csv .= "20240001,15,10,60\n";
        $csv .= "20240002,20,15,55\n";

        return response()->make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="result_upload_template.csv"',
        ]);
    }

    public function approve(Request $request, Result $result)
    {
        $result->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Result approved successfully');
    }

    public function reject(Request $request, Result $result)
    {
        $result->update([
            'status' => 'rejected',
            'remarks' => $request->remarks,
        ]);

        return back()->with('success', 'Result rejected');
    }
}