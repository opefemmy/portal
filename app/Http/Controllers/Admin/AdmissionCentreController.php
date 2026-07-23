<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdmissionCentre;
use Illuminate\Http\Request;

class AdmissionCentreController extends Controller
{
    /**
     * Display a listing of centres.
     */
    public function index()
    {
        $centres = AdmissionCentre::orderBy('name')->get();
        return view('admin.admission-centres.index', compact('centres'));
    }

    /**
     * Show the form for creating a new centre.
     */
    public function create()
    {
        return view('admin.admission-centres.create');
    }

    /**
     * Store a newly created centre.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:admission_centres,code',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'is_active' => 'boolean',
        ]);

        AdmissionCentre::create($validated);

        return redirect()->route('admin.admission-centres.index')
            ->with('success', 'Admission centre created successfully!');
    }

    /**
     * Show the form for editing a centre.
     */
    public function edit(AdmissionCentre $centre)
    {
        return view('admin.admission-centres.edit', compact('centre'));
    }

    /**
     * Update the specified centre.
     */
    public function update(Request $request, AdmissionCentre $centre)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:admission_centres,code,' . $centre->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'is_active' => 'boolean',
        ]);

        $centre->update($validated);

        return redirect()->route('admin.admission-centres.index')
            ->with('success', 'Admission centre updated successfully!');
    }

    /**
     * Remove the specified centre.
     */
    public function destroy(AdmissionCentre $centre)
    {
        // Check if centre has applicants
        if ($centre->applicants()->count() > 0) {
            return back()->with('error', 'Cannot delete centre with associated applicants.');
        }

        $centre->delete();

        return redirect()->route('admin.admission-centres.index')
            ->with('success', 'Admission centre deleted successfully!');
    }

    /**
     * Toggle centre status.
     */
    public function toggleStatus(AdmissionCentre $centre)
    {
        $centre->update(['is_active' => !$centre->is_active]);

        $status = $centre->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Centre {$status} successfully!");
    }
}
