<?php

namespace App\Http\Controllers\Hospital;

use App\Events\Hospital\BedAssigned;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Hospital\HospitalAdmission;
use App\Models\Hospital\HospitalBed;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalStaff;
use App\Models\Hospital\HospitalWard;
use App\Services\Hospital\AuditTrail;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Ward management + bed-assignment for matron/ward_manager.
 *
 * Matron and ward_manager are the only roles allowed to:
 *   - create / edit wards
 *   - assign a patient to a bed (creating the HospitalAdmission row)
 *   - discharge a bed (closing the HospitalAdmission, freeing the bed)
 *
 * Every write goes through AuditTrail::record and emits an event so the
 * matron-side notifications stay accurate.
 */
class WardController extends Controller
{
    use EnforcesPermission;

    /**
     * Ward list with occupancy percentages.
     */
    public function index()
    {
        $this->requirePermission('wards.view');

        $wards = HospitalWard::withCount(['beds', 'availableBeds', 'occupiedBeds'])
            ->where('is_active', true)
            ->orderBy('name')
            ->paginate(20);

        return view('hospital.wards.index', compact('wards'));
    }

    /**
     * Form to create a new ward.
     */
    public function create()
    {
        $this->requirePermission('wards.manage');

        return view('hospital.wards.create');
    }

    /**
     * Persist a new ward row.
     */
    public function store(Request $request)
    {
        $this->requirePermission('wards.manage');

        $data = $this->validateWard($request);

        $ward = HospitalWard::create($data);

        AuditTrail::record('ward.create', $ward, null, [], $ward->toArray());

        return redirect()
            ->route('hospital.wards.index')
            ->with('success', "Ward {$ward->name} created.");
    }

    /**
     * Form to edit a ward.
     */
    public function edit(HospitalWard $ward)
    {
        $this->requirePermission('wards.manage');

        return view('hospital.wards.edit', compact('ward'));
    }

    /**
     * Persist ward changes.
     */
    public function update(Request $request, HospitalWard $ward)
    {
        $this->requirePermission('wards.manage');

        $data = $this->validateWard($request, $ward);

        $before = $ward->toArray();
        $ward->update($data);

        AuditTrail::record('ward.update', $ward, null, $before, $ward->fresh()->toArray());

        return redirect()
            ->route('hospital.wards.index')
            ->with('success', "Ward {$ward->name} updated.");
    }

    /**
     * Show a ward's beds with current patients.
     */
    public function beds(HospitalWard $ward)
    {
        $this->requirePermission('wards.view');

        $beds = $ward->beds()
            ->with(['patient', 'admissions' => function ($q) {
                $q->where('status', 'admitted')->latest('admission_date');
            }])
            ->orderBy('bed_number')
            ->get();

        $availablePatients = HospitalPatient::where('is_active', true)
            ->whereDoesntHave('admissions', function ($q) {
                $q->where('status', 'admitted');
            })
            ->orderBy('last_name')
            ->limit(50)
            ->get();

        $doctors = HospitalStaff::where('staff_type', 'doctor')
            ->where('is_active', true)
            ->orderBy('last_name')
            ->get();

        return view('hospital.wards.beds', compact('ward', 'beds', 'availablePatients', 'doctors'));
    }

    /**
     * Bind a HospitalPatient to a free bed and create a HospitalAdmission
     * row. Wrapped in a transaction so a failed bed-mark doesn't leave an
     * admission without a bed.
     */
    public function assignBed(Request $request, HospitalWard $ward): RedirectResponse
    {
        $this->requirePermission('beds.assign');

        $data = $request->validate([
            'bed_id'     => ['required', 'integer', Rule::exists('hospital_beds', 'id')->where('ward_id', $ward->id)],
            'patient_id' => ['required', 'integer', Rule::exists('hospital_patients', 'id')],
            'doctor_id'  => ['required', 'integer', Rule::exists('hospital_staff', 'id')],
            'reason'     => ['required', 'string', 'max:500'],
            'diagnosis'  => ['nullable', 'string', 'max:1000'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bed = HospitalBed::findOrFail($data['bed_id']);
        if ($bed->status !== 'available') {
            return back()->with('error', "Bed {$bed->bed_number} is no longer available.");
        }

        $admission = DB::transaction(function () use ($data, $bed, $ward) {
            $bed->update([
                'status'      => 'occupied',
                'patient_id'  => $data['patient_id'],
                'occupied_at' => now(),
            ]);

            return HospitalAdmission::create([
                'patient_id'      => $data['patient_id'],
                'doctor_id'       => $data['doctor_id'],
                'bed_id'          => $bed->id,
                'admission_number'=> 'ADM-' . now()->format('YmdHis') . '-' . random_int(100, 999),
                'admission_date'  => now(),
                'status'          => 'admitted',
                'reason'          => $data['reason'],
                'diagnosis'       => $data['diagnosis'] ?? null,
                'daily_rate'      => $data['daily_rate'] ?? $ward->daily_rate,
            ]);
        });

        $ward->refreshAvailableBeds();

        AuditTrail::record('ward.bed.assign', $bed, $data['patient_id'], [], [
            'bed' => $bed->toArray(),
            'admission_id' => $admission->id,
        ]);

        event(new BedAssigned($admission->id));

        return redirect()
            ->route('hospital.wards.beds', $ward)
            ->with('success', 'Patient assigned and admission opened.');
    }

    /**
     * Close an admission and free its bed. Records the discharge notes
     * the matron typed and the date automatically.
     */
    public function dischargeBed(Request $request, HospitalBed $bed): RedirectResponse
    {
        $this->requirePermission('beds.manage');

        $data = $request->validate([
            'discharge_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $admission = $bed->admissions()->where('status', 'admitted')->latest('admission_date')->first();
        if (! $admission) {
            return back()->with('error', 'No open admission found for this bed.');
        }

        DB::transaction(function () use ($bed, $admission, $data) {
            $admission->update([
                'status'          => 'discharged',
                'discharge_date'  => now(),
                'discharge_notes' => $data['discharge_notes'] ?? null,
            ]);

            $bed->update([
                'status'        => 'available',
                'patient_id'    => null,
                'discharged_at' => now(),
            ]);
        });

        $bed->ward->refreshAvailableBeds();

        AuditTrail::record('ward.bed.discharge', $bed, $admission->patient_id, [], [
            'admission_id'   => $admission->id,
            'discharge_date' => now()->toDateTimeString(),
        ]);

        return redirect()
            ->route('hospital.wards.beds', $bed->ward)
            ->with('success', 'Patient discharged and bed freed.');
    }

    /**
     * Whole-hospital occupancy report.
     */
    public function occupancyReport()
    {
        $this->requirePermission('wards.view');

        $wards = HospitalWard::withCount(['beds', 'availableBeds', 'occupiedBeds'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $totalBeds    = $wards->sum('beds_count');
        $occupiedBeds = $wards->sum('occupied_beds_count');
        $availableBeds= $wards->sum('available_beds_count');

        $avgStay = HospitalAdmission::where('status', 'discharged')
            ->whereNotNull('discharge_date')
            ->selectRaw('AVG(TIMESTAMPDIFF(DAY, admission_date, discharge_date)) as avg_days')
            ->value('avg_days');

        $dischargesByDay = HospitalAdmission::where('status', 'discharged')
            ->where('discharge_date', '>=', now()->subDays(30))
            ->selectRaw('DATE(discharge_date) as day, COUNT(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        return view('hospital.wards.occupancy', compact(
            'wards', 'totalBeds', 'occupiedBeds', 'availableBeds', 'avgStay', 'dischargesByDay'
        ));
    }

    /**
     * Shared ward validation rules.
     *
     * @return array<string,mixed>
     */
    protected function validateWard(Request $request, ?HospitalWard $ward = null): array
    {
        return $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'type'         => ['required', 'string', 'max:40'],
            'total_beds'   => ['required', 'integer', 'min:1', 'max:500'],
            'daily_rate'   => ['required', 'numeric', 'min:0'],
            'description'  => ['nullable', 'string', 'max:1000'],
            'is_active'    => ['nullable', 'boolean'],
        ]);
    }
}