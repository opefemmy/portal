<?php

namespace App\Http\Controllers\Bursar;

use App\Http\Controllers\Controller;
use App\Models\RegimePayment;
use Illuminate\Http\Request;

class RegimeController extends Controller
{
    public function index()
    {
        $regimes = RegimePayment::latest()->get();
        return view('bursar.regimes.index', compact('regimes'));
    }

    public function create()
    {
        return view('bursar.regimes.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_type' => 'required|in:Indigene,Non-Indigene',
            'installment' => 'required|in:First,Second,Full',
            'percentage' => 'required|numeric|min:1|max:100',
            'amount' => 'nullable|numeric|min:0',
        ]);

        RegimePayment::create($validated);
        return redirect()->route('bursar.regimes.index')->with('success', 'Regime payment created');
    }

    public function edit(RegimePayment $regime)
    {
        return view('bursar.regimes.edit', compact('regime'));
    }

    public function update(Request $request, RegimePayment $regime)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'student_type' => 'required|in:Indigene,Non-Indigene',
            'installment' => 'required|in:First,Second,Full',
            'percentage' => 'required|numeric|min:1|max:100',
            'amount' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $regime->update($validated);
        return redirect()->route('bursar.regimes.index')->with('success', 'Regime payment updated');
    }

    public function destroy(RegimePayment $regime)
    {
        $regime->delete();
        return back()->with('success', 'Regime deleted');
    }
}