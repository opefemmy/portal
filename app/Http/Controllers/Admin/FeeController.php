<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    public function index()
    {
        $fees = Fee::with('school', 'department', 'programme', 'session')->latest()->get();
        return view('admin.fees.index', compact('fees'));
    }

    public function create()
    {
        $data = [
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
            'sessions' => Session::all(),
        ];
        return view('admin.fees.create', $data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'payment_type' => 'required|in:Tuition Fee,Departmental Fee,Other',
            'amount' => 'required|numeric|min:0',
            'school_id' => 'nullable|exists:schools,id',
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'level' => 'nullable|integer|min:1|max:6',
            'session_id' => 'required|exists:sessions,id',
            'due_date' => 'nullable|date',
        ]);

        Fee::create($validated);
        return redirect()->route('admin.fees.index')->with('success', 'Fee created');
    }

    public function edit(Fee $fee)
    {
        $data = [
            'fee' => $fee,
            'schools' => School::all(),
            'departments' => Department::all(),
            'programmes' => Programme::all(),
            'sessions' => Session::all(),
        ];
        return view('admin.fees.edit', $data);
    }

    public function update(Request $request, Fee $fee)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'payment_type' => 'required|in:Tuition Fee,Departmental Fee,Other',
            'amount' => 'required|numeric|min:0',
            'school_id' => 'nullable|exists:schools,id',
            'department_id' => 'nullable|exists:departments,id',
            'programme_id' => 'nullable|exists:programmes,id',
            'level' => 'nullable|integer|min:1|max:6',
            'session_id' => 'required|exists:sessions,id',
            'due_date' => 'nullable|date',
        ]);

        $fee->update($validated);
        return redirect()->route('admin.fees.index')->with('success', 'Fee updated');
    }

    public function destroy(Fee $fee)
    {
        $fee->delete();
        return back()->with('success', 'Fee deleted');
    }
}