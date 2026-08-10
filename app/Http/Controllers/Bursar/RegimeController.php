<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\RegimePayment;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegimeController extends Controller
{
    public function index()
    {
        $regimes = RegimePayment::with(['school', 'department', 'programme', 'session'])
            ->latest()
            ->get();

        // Tally: for each regime, sum the actual completed payments that
        // match its scope (school/department/programme/session/student_type).
        // Counts how many students have paid under this regime and the
        // total collected. This is what makes the regimes view reconcile
        // with /bursar/payments and /bursar/reports.
        $tallyByRegime = [];
        foreach ($regimes as $regime) {
            $paymentsQuery = Payment::query()
                ->where('status', 'completed')
                ->where('installment', $regime->installment);

            // Only match payments whose fee_type / payment_purpose matches
            // the regime's payment_type when both are set.
            if ($regime->payment_type) {
                $paymentsQuery->where(function ($q) use ($regime) {
                    $q->where('fee_type', $regime->payment_type)
                      ->orWhere('payment_purpose', $regime->payment_type);
                });
            }

            // Scope filters: only apply if the regime has them set.
            if ($regime->school_id) {
                $paymentsQuery->whereHas('student', function ($q) use ($regime) {
                    $q->where('school_id', $regime->school_id);
                });
            }
            if ($regime->department_id) {
                $paymentsQuery->whereHas('student', function ($q) use ($regime) {
                    $q->where('department_id', $regime->department_id);
                });
            }
            if ($regime->programme_id) {
                $paymentsQuery->whereHas('student', function ($q) use ($regime) {
                    $q->where('programme_id', $regime->programme_id);
                });
            }
            if ($regime->session_id) {
                $paymentsQuery->whereHas('student', function ($q) use ($regime) {
                    $q->where('session_id', $regime->session_id);
                });
            }
            if ($regime->student_type) {
                $paymentsQuery->where('student_type', $regime->student_type);
            }

            $tallyByRegime[$regime->id] = [
                'students' => (clone $paymentsQuery)
                    ->whereNotNull('student_id')
                    ->distinct('student_id')
                    ->count('student_id'),
                'count'    => $paymentsQuery->count(),
                'amount'   => $paymentsQuery->sum('amount'),
            ];
        }

        return view('bursar.regimes.index', compact('regimes', 'tallyByRegime'));
    }

    public function create()
    {
        $schools = School::all();
        $departments = Department::all();
        $programmes = Programme::all();
        $sessions = Session::where('is_current', true)->get();

        return view('bursar.regimes.create', compact('schools', 'departments', 'programmes', 'sessions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_type' => 'required|in:Indigene,Non-Indigene',
            'payment_type' => 'required|in:school_fee,accommodation,acceptance_fee,other',
            'installment' => 'required|in:First,Second,Full',
            'percentage' => 'required|numeric|min:1|max:100',
            'amount' => 'nullable|numeric|min:0',
            'portal_charge' => 'nullable|numeric|min:0',
            'include_portal_charge' => 'boolean',
            'payment_config' => 'required|in:full,60_40,70_30,50_50',
            // Scope fields (optional)
            'school_id' => 'nullable|exists:schools,id',
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'session_id' => 'nullable|exists:sessions,id',
            'semester' => 'nullable|in:first,second,both',
            'level' => 'nullable|integer|min:1|max:6',
            'level_operator' => 'nullable|in:exact,minimum,maximum',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['include_portal_charge'] = $request->boolean('include_portal_charge', false);

        RegimePayment::create($validated);
        return redirect()->route('bursar.regimes.index')->with('success', 'Regime payment created successfully');
    }

    public function edit(RegimePayment $regime)
    {
        $this->assertSameSchool($regime);
        $schools = School::all();
        $departments = Department::all();
        $programmes = Programme::all();
        $sessions = Session::all();

        return view('bursar.regimes.edit', compact('regime', 'schools', 'departments', 'programmes', 'sessions'));
    }

    public function update(Request $request, RegimePayment $regime)
    {
        $this->assertSameSchool($regime);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_type' => 'required|in:Indigene,Non-Indigene',
            'payment_type' => 'required|in:school_fee,accommodation,acceptance_fee,other',
            'installment' => 'required|in:First,Second,Full',
            'percentage' => 'required|numeric|min:1|max:100',
            'amount' => 'nullable|numeric|min:0',
            'portal_charge' => 'nullable|numeric|min:0',
            'include_portal_charge' => 'boolean',
            'payment_config' => 'required|in:full,60_40,70_30,50_50',
            'school_id' => 'nullable|exists:schools,id',
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'session_id' => 'nullable|exists:sessions,id',
            'semester' => 'nullable|in:first,second,both',
            'level' => 'nullable|integer|min:1|max:6',
            'level_operator' => 'nullable|in:exact,minimum,maximum',
            'is_active' => 'boolean',
        ]);

        $validated['include_portal_charge'] = $request->boolean('include_portal_charge', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        $regime->update($validated);
        return redirect()->route('bursar.regimes.index')->with('success', 'Regime payment updated successfully');
    }

    public function destroy(RegimePayment $regime)
    {
        $this->assertSameSchool($regime);
        $regime->delete();
        return back()->with('success', 'Regime deleted successfully');
    }

    private function assertSameSchool(RegimePayment $regime): void
    {
        $authUser = auth()->user();
        if (!$authUser) {
            abort(401);
        }
        // Super admins bypass the school check; everyone else must match.
        if ($authUser->school_id
            && $regime->school_id
            && (int) $regime->school_id !== (int) $authUser->school_id) {
            abort(403, 'You are not allowed to access this regime.');
        }
    }
}