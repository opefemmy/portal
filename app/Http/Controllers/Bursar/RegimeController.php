<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\RegimePayment;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;

class RegimeController extends Controller
{
    public function index()
    {
        $regimes = RegimePayment::with(['school', 'department', 'programme', 'session'])
            ->latest()
            ->get();
        return view('bursar.regimes.index', compact('regimes'));
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
        $schools = School::all();
        $departments = Department::all();
        $programmes = Programme::all();
        $sessions = Session::all();

        return view('bursar.regimes.edit', compact('regime', 'schools', 'departments', 'programmes', 'sessions'));
    }

    public function update(Request $request, RegimePayment $regime)
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
        $regime->delete();
        return back()->with('success', 'Regime deleted successfully');
    }
}