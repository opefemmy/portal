<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesHospitalPermission;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalAppointment;
use App\Models\Hospital\HospitalBed;
use App\Models\Hospital\HospitalDutyRoster;
use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalDrugBatch;
use App\Models\Hospital\HospitalLabRequest;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalPayment;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalStaff;
use App\Services\Hospital\AuditTrail;
use Carbon\Carbon;

/**
 * Cross-cutting hospital_admin dashboard — read-mostly KPIs across every
 * module (appointments, admissions, beds, prescriptions, lab, revenue,
 * staff, duty roster attendance). Also exposes the staff and revenue
 * detail pages, an inventory snapshot, and an attendance summary.
 *
 * Writes are limited to staff availability toggles (`staff.toggle`) and
 * are audited.
 */
class HospitalAdminController extends Controller
{
    use EnforcesHospitalPermission;

    /**
     * Top-level KPIs and activity feed.
     */
    public function index()
    {
        $this->requirePermission('reports.daily-revenue');

        $stats = [
            'today_appointments'   => HospitalAppointment::whereDate('appointment_date', today())->count(),
            'pending_appointments' => HospitalAppointment::where('status', 'scheduled')->count(),
            'inpatients'           => HospitalAdmission::where('status', 'admitted')->count(),
            'available_beds'       => HospitalBed::where('status', 'available')->count(),
            'occupied_beds'        => HospitalBed::where('status', 'occupied')->count(),
            'pending_prescriptions'=> HospitalPrescription::where('status', 'pending')->count(),
            'pending_lab'          => HospitalLabRequest::whereIn('status', ['pending', 'sample_collected'])->count(),
            'revenue_today'        => (float) HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
                ->whereDate('payment_date', today())->sum('total_amount'),
            'revenue_month'        => (float) HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
                ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum('total_amount'),
            'total_staff'          => HospitalStaff::where('is_active', true)->count(),
            'staff_available'      => HospitalStaff::where('is_active', true)->where('is_available', true)->count(),
            'total_patients'       => HospitalPatient::count(),
            'new_patients_today'   => HospitalPatient::whereDate('created_at', today())->count(),
            'low_stock_items'      => HospitalDrug::whereColumn('current_stock', '<=', 'reorder_level')->count(),
        ];

        $recentAdmissions = HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])
            ->latest('admission_date')->limit(8)->get();

        $revenueByDay = HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
            ->where('payment_date', '>=', now()->subDays(13)->toDateString())
            ->selectRaw('DATE(payment_date) as day, SUM(total_amount) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->pluck('total', 'day');

        return view('hospital.admin.dashboard', compact('stats', 'recentAdmissions', 'revenueByDay'));
    }

    /**
     * Staff directory — paginated, with optional role filter.
     */
    public function staff(Request $request)
    {
        $this->requirePermission('staff.view');

        $query = HospitalStaff::query();
        if ($type = $request->query('staff_type')) {
            $query->where('staff_type', $type);
        }
        $staff = $query->orderBy('staff_type')->orderBy('last_name')->paginate(25)->withQueryString();
        $types = ['doctor', 'nurse', 'pharmacist', 'lab_scientist', 'receptionist', 'cashier', 'admin', 'matron', 'ward_manager'];

        return view('hospital.admin.staff', compact('staff', 'types'));
    }

    /**
     * Toggle a staff member's availability flag (used by on-call grid).
     */
    public function toggleAvailability(HospitalStaff $staff)
    {
        $this->requirePermission('staff.edit');

        $before = $staff->toArray();
        $staff->update(['is_available' => ! $staff->is_available]);
        AuditTrail::record('staff.availability.toggle', $staff, null, $before, $staff->fresh()->toArray());

        return back()->with('success', "{$staff->full_name} availability updated.");
    }

    /**
     * Revenue page — daily totals + monthly total + breakdown by service type.
     */
    public function revenue(Request $request)
    {
        $this->requirePermission('reports.daily-revenue');

        $days = max(7, min(60, (int) $request->query('days', 14)));
        $since = now()->subDays($days - 1)->toDateString();

        $daily = HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
            ->where('payment_date', '>=', $since)
            ->selectRaw('DATE(payment_date) as day, SUM(total_amount) as total, COUNT(*) as cnt')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $byService = HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
            ->where('payment_date', '>=', $since)
            ->selectRaw('service_name, SUM(total_amount) as total, COUNT(*) as cnt')
            ->groupBy('service_name')
            ->orderByDesc('total')
            ->get();

        $byMethod = HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
            ->where('payment_date', '>=', $since)
            ->selectRaw('payment_method, SUM(total_amount) as total, COUNT(*) as cnt')
            ->groupBy('payment_method')
            ->orderByDesc('total')
            ->get();

        $monthTotal = (float) HospitalPayment::where('status', HospitalPayment::STATUS_COMPLETED)
            ->whereBetween('payment_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('total_amount');

        return view('hospital.admin.revenue', compact('daily', 'byService', 'byMethod', 'monthTotal', 'days'));
    }

    /**
     * Inventory snapshot — low-stock items and batches expiring in the next 60 days.
     */
    public function inventory()
    {
        $this->requirePermission('inventory.view');

        $lowStock = HospitalDrug::whereColumn('current_stock', '<=', 'reorder_level')
            ->orderBy('current_stock')
            ->limit(40)
            ->get();

        $expiringSoon = HospitalDrugBatch::with('drug')
            ->where('status', 'active')
            ->whereDate('expiry_date', '<=', now()->addDays(60)->toDateString())
            ->whereDate('expiry_date', '>=', today()->toDateString())
            ->orderBy('expiry_date')
            ->limit(40)
            ->get();

        $totalStockValue = (float) HospitalDrug::query()
            ->selectRaw('SUM(current_stock * cost_price) as v')
            ->value('v');

        return view('hospital.admin.inventory', compact('lowStock', 'expiringSoon', 'totalStockValue'));
    }

    /**
     * Duty roster attendance — last 7 days: scheduled vs present.
     */
    public function attendance()
    {
        $this->requirePermission('attendance.view');

        $since = now()->subDays(6)->toDateString();

        $rows = HospitalDutyRoster::with('staff')
            ->where('duty_date', '>=', $since)
            ->where('is_active', true)
            ->orderBy('duty_date')
            ->orderBy('start_time')
            ->get()
            ->groupBy(fn ($r) => Carbon::parse($r->duty_date)->toDateString());

        $summary = $rows->map(function ($group, $day) {
            return [
                'day'      => $day,
                'total'    => $group->count(),
                'filled'   => $group->whereNotNull('staff_id')->count(),
                'no_shows' => 0,
            ];
        })->values();

        return view('hospital.admin.attendance', compact('rows', 'summary'));
    }
}