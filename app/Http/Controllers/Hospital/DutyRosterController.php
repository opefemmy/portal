<?php

namespace App\Http\Controllers\Hospital;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalDutyRoster;
use App\Models\Hospital\HospitalStaff;
use App\Services\Hospital\AuditTrail;
use Illuminate\Http\Request;


class DutyRosterController extends Controller
{
    use EnforcesPermission;

    /**
     * Weekly roster view.
     */
    public function index(Request $request)
    {
        $this->requirePermission('patients.view');

        $weekStart = $request->query('week_start')
            ? \Carbon\Carbon::parse($request->query('week_start'))->startOfWeek()
            : now()->startOfWeek();

        $weekEnd = $weekStart->copy()->endOfWeek();

        $roster = HospitalDutyRoster::with('staff')
            ->whereBetween('duty_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->orderBy('duty_date')
            ->orderBy('shift')
            ->get();

        $staff = HospitalStaff::where('is_active', true)
            ->orderBy('first_name')
            ->get();

        return view('hospital.roster.index', [
            'roster'    => $roster,
            'staff'     => $staff,
            'weekStart' => $weekStart,
            'weekEnd'   => $weekEnd,
        ]);
    }

    /**
     * Save a roster entry.
     */
    public function store(Request $request)
    {
        $this->requirePermission('patients.view');

        $data = $request->validate([
            'staff_id'  => 'required|exists:hospital_staff,id',
            'duty_date' => 'required|date',
            'start_time'=> 'required|date_format:H:i',
            'end_time'  => 'required|date_format:H:i|after:start_time',
            'shift'     => 'required|in:morning,evening,night,on_call',
            'location'  => 'nullable|string|max:120',
            'notes'     => 'nullable|string',
        ]);

        $entry = HospitalDutyRoster::create($data);

        AuditTrail::record('roster.create', $entry, null, [], $entry->toArray());

        return back()->with('success', 'Roster entry saved.');
    }

    /**
     * Delete a roster entry.
     */
    public function destroy(HospitalDutyRoster $entry)
    {
        $this->requirePermission('patients.view');

        $entry->delete();
        return back()->with('success', 'Roster entry removed.');
    }
}