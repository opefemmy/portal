<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hospital\HospitalServiceType;
use Illuminate\Http\Request;

class HospitalServiceController extends Controller
{
    public function index()
    {
        $services = HospitalServiceType::orderBy('category')->orderBy('name')->get();
        return view('admin.hospital-services.index', compact('services'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'requires_appointment' => 'boolean',
        ]);

        HospitalServiceType::create([
            'name' => $request->name,
            'category' => $request->category,
            'amount' => $request->amount,
            'is_active' => $request->is_active ?? true,
            'requires_appointment' => $request->requires_appointment ?? false,
        ]);

        return back()->with('success', 'Hospital service created successfully!');
    }

    public function update(Request $request, HospitalServiceType $service)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0',
            'requires_appointment' => 'boolean',
        ]);

        $service->update([
            'name' => $request->name,
            'category' => $request->category,
            'amount' => $request->amount,
            'is_active' => $request->is_active ?? true,
            'requires_appointment' => $request->requires_appointment ?? false,
        ]);

        return back()->with('success', 'Hospital service updated successfully!');
    }

    public function destroy(HospitalServiceType $service)
    {
        $service->delete();
        return back()->with('success', 'Hospital service deleted successfully!');
    }

    public function toggleStatus(HospitalServiceType $service)
    {
        $service->update(['is_active' => !$service->is_active]);
        $status = $service->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "Service {$status} successfully!");
    }
}
