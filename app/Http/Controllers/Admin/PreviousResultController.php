<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PreviousResult;
use App\Models\Student;
use App\Services\PreviousResultImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bulk-import historical result rows (from previous institutions or
 * earlier sessions) so they show up on the student's transcript.
 *
 * Distinct from admin/ResultController — that one manages *current*
 * results that have a student_course_id link; this one manages
 * standalone rows that don't.
 */
class PreviousResultController extends Controller
{
    public function index(Request $request)
    {
        $query = PreviousResult::with(['student.user', 'uploader']);

        if ($request->student_id) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->session_name) {
            $query->where('session_name', $request->session_name);
        }

        $results = $query->latest('uploaded_at')->paginate(20)->withQueryString();
        $students = Student::with('user')->orderBy('matric_number')->get(['id', 'matric_number', 'user_id']);

        return view('admin.previous-results.index', compact('results', 'students'));
    }

    public function create()
    {
        return view('admin.previous-results.upload');
    }

    public function upload(Request $request, PreviousResultImporter $importer)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xlsm,txt|max:5120', // 5MB
        ]);

        try {
            $summary = $importer->import($request->file('file'), $request->user()->id);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        // Persist a flash message that survives the redirect with both
        // counts so the admin can see what worked and what didn't.
        return back()->with(
            'import_summary',
            ['imported' => $summary['imported'], 'errors' => $summary['errors']]
        );
    }

    public function downloadTemplate(PreviousResultImporter $importer)
    {
        $csv = $importer->templateCsv();
        return response($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="previous_results_template.csv"',
        ]);
    }

    public function destroy(PreviousResult $previousResult)
    {
        $previousResult->delete();
        return back()->with('success', 'Previous-result row deleted.');
    }

    /**
     * Wipe ALL rows for a student (used by the admin to re-import after
     * a typo). Guarded by a confirmation flag in the request.
     */
    public function purgeForStudent(Request $request, Student $student)
    {
        if (!$request->boolean('confirm')) {
            return back()->with('error', 'Confirmation flag missing. Re-submit with confirm=1.');
        }
        $count = PreviousResult::where('student_id', $student->id)->count();
        PreviousResult::where('student_id', $student->id)->delete();
        return back()->with('success', "Cleared {$count} previous-result row(s) for {$student->matric_number}.");
    }
}