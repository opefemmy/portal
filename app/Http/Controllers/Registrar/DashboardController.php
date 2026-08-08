<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Render the registrar dashboard with admission pipeline stats.
     *
     * Counts are grouped by applicants.status so the registrar can see
     * the funnel at a glance. The pipeline stats do NOT touch
     * external_payments — that table is only loaded by the applicant
     * dashboard and the bursar/receipt pages, so no Schema::hasTable
     * guard is needed here.
     */
    public function index(Request $request)
    {
        $schoolId = $request->query('school_id');

        // --- Pipeline counts ---
        // Group by status in one round trip instead of four separate
        // COUNT queries. Tolerant of legacy DBs missing the table.
        $statusCounts = collect();
        if (Schema::hasTable('applicants')) {
            $query = Applicant::query();
            if ($schoolId) {
                $query->where('school_id', $schoolId);
            }
            $statusCounts = $query->select('status', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status');
        }

        $stats = [
            'total'      => $statusCounts->sum(),
            'pending'    => (int) ($statusCounts['pending']   ?? 0),
            'screening'  => (int) ($statusCounts['screening'] ?? 0),
            'approved'   => (int) ($statusCounts['approved']  ?? 0),
            'admitted'   => (int) ($statusCounts['admitted']  ?? 0),
            'rejected'   => (int) ($statusCounts['rejected']  ?? 0),
        ];

        // --- Recent applicants (last 5) ---
        $recentApplicants = collect();
        if (Schema::hasTable('applicants')) {
            $recentApplicants = Applicant::with(['school', 'department', 'programme'])
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->latest('created_at')
                ->limit(5)
                ->get();
        }

        // --- Recent admissions (last 5 admitted) ---
        $recentAdmissions = collect();
        if (Schema::hasTable('applicants')) {
            $recentAdmissions = Applicant::with(['school', 'department', 'programme'])
                ->where('status', 'admitted')
                ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
                ->latest('updated_at')
                ->limit(5)
                ->get();
        }

        $schools = Schema::hasTable('schools')
            ? School::orderBy('name')->get(['id', 'name'])
            : collect();

        return view('registrar.dashboard', compact(
            'stats',
            'recentApplicants',
            'recentAdmissions',
            'schools',
            'schoolId'
        ));
    }
}
