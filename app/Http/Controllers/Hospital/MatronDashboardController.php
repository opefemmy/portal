<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalBed;
use App\Models\Hospital\HospitalDutyRoster;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaff;
use App\Models\Hospital\HospitalWard;
use App\Services\Dashboard\DashboardResolver;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Matron dashboard — senior-nurse ward operations oversight.
 *
 * Read-only summary across wards, admissions, duty roster and rounds.
 * Reuses existing models (HospitalWard, HospitalBed, HospitalAdmission,
 * HospitalDutyRoster, HospitalStaff, HospitalPatient) without introducing
 * new tables.
 */
class MatronDashboardController extends Controller
{
    use EnforcesPermission;

    /**
     * Matron landing page — KPIs, today's discharges, ward occupancy,
     * nurses on duty, recent admissions.
     */
    public function index(Request $request)
    {
        $this->requirePermission('wards.view');

        $widgets = DashboardResolver::widgetsForUser($request->user());

        $wards = HospitalWard::withCount(['beds', 'availableBeds', 'occupiedBeds'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $recentAdmissions = HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])
            ->where('status', 'admitted')
            ->latest('admission_date')
            ->limit(10)
            ->get();

        $upcomingRoster = HospitalDutyRoster::with('staff')
            ->where('duty_date', '>=', today())
            ->where('duty_date', '<=', today()->addDays(2))
            ->where('is_active', true)
            ->orderBy('duty_date')
            ->orderBy('start_time')
            ->limit(15)
            ->get();

        return view('hospital.matron.dashboard', compact(
            'widgets', 'wards', 'recentAdmissions', 'upcomingRoster'
        ));
    }

    /**
     * Current inpatients list — used by matron during ward rounds.
     */
    public function rounds()
    {
        $this->requirePermission('patients.view');

        $inpatients = HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])
            ->where('status', 'admitted')
            ->orderBy('admission_date')
            ->paginate(25);

        $wards = HospitalWard::where('is_active', true)->orderBy('name')->get();

        return view('hospital.matron.rounds', compact('inpatients', 'wards'));
    }

    /**
     * Weekly duty roster + per-nurse patient load.
     */
    public function staffLoad()
    {
        $this->requirePermission('monitoring.notes');

        $weekStart = Carbon::parse('this week')->startOfWeek();
        $weekEnd   = $weekStart->copy()->endOfWeek();

        $roster = HospitalDutyRoster::with('staff')
            ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->where('is_active', true)
            ->orderBy('duty_date')
            ->orderBy('start_time')
            ->get();

        // Per-staff patient load (count of open admissions assigned to a
        // doctor whose user matches the staff member). Falls back to total
        // admissions grouped by doctor_id when no direct link exists.
        $staffLoad = HospitalStaff::where('staff_type', 'nurse')
            ->withCount(['appointments' => function ($q) use ($weekStart, $weekEnd) {
                $q->whereBetween('appointment_date', [$weekStart, $weekEnd]);
            }])
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        return view('hospital.matron.staff', compact('roster', 'staffLoad', 'weekStart', 'weekEnd'));
    }
}