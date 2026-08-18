<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\School;
use App\Models\Department;
use App\Models\Programme;
use App\Models\Session;
use Illuminate\Http\Request;

class FeeController extends Controller
{
    use EnforcesPermission;

    public function index()
    {
        $this->requirePermission('admin.fees.manage');
        $fees = Fee::with('school', 'department', 'programme', 'session')->latest()->get();
        return view('admin.fees.index', compact('fees'));
    }

    public function create()
    {
        $this->requirePermission('admin.fees.manage');
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
        $this->requirePermission('admin.fees.manage');
        $category = $request->input('category', 'both');

        // Per-category amount columns are required when the matching
        // category is selected. `amount` stays optional — the controller
        // copies the per-category value into it as a back-compat shim.
        $rules = [
            'name'           => 'required|string|max:255',
            'payment_type'   => 'required|in:Tuition Fee,Departmental Fee,Other',
            'category'       => 'required|in:both,indigene,non_indigene,portal_charge',
            'amount'         => 'nullable|numeric|min:0',
            'indigene_amount'    => 'nullable|numeric|min:0',
            'non_indigene_amount'=> 'nullable|numeric|min:0',
            'portal_charge'      => 'nullable|numeric|min:0',
            'school_id'      => 'nullable|exists:schools,id',
            'department_id'  => 'nullable|exists:departments,id',
            'programme_id'   => 'nullable|exists:programmes,id',
            'level'          => 'nullable|integer|min:1|max:6',
            'session_id'     => 'required|exists:sessions,id',
            'due_date'       => 'nullable|date',
        ];

        if (in_array($category, ['both', 'indigene'], true)) {
            $rules['indigene_amount'] = 'required|numeric|min:0';
        }
        if (in_array($category, ['both', 'non_indigene'], true)) {
            $rules['non_indigene_amount'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);
        $validated['portal_charge'] = (float) ($validated['portal_charge'] ?? 0);

        // Mirror the active category's amount into the legacy `amount`
        // column so existing queries and reports keep working.
        $validated['amount'] = $this->resolveLegacyAmount($validated, $category);

        Fee::create($validated);
        return redirect()->route('admin.fees.index')->with('success', 'Fee created');
    }

    public function edit(Fee $fee)
    {
        $this->requirePermission('admin.fees.manage');
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
        $this->requirePermission('admin.fees.manage');
        $category = $request->input('category', $fee->category ?? 'both');

        $rules = [
            'name'           => 'required|string|max:255',
            'payment_type'   => 'required|in:Tuition Fee,Departmental Fee,Other',
            'category'       => 'required|in:both,indigene,non_indigene,portal_charge',
            'amount'         => 'nullable|numeric|min:0',
            'indigene_amount'    => 'nullable|numeric|min:0',
            'non_indigene_amount'=> 'nullable|numeric|min:0',
            'portal_charge'      => 'nullable|numeric|min:0',
            'school_id'      => 'nullable|exists:schools,id',
            'department_id'  => 'nullable|exists:departments,id',
            'programme_id'   => 'nullable|exists:programmes,id',
            'level'          => 'nullable|integer|min:1|max:6',
            'session_id'     => 'required|exists:sessions,id',
            'due_date'       => 'nullable|date',
        ];

        if (in_array($category, ['both', 'indigene'], true)) {
            $rules['indigene_amount'] = 'required|numeric|min:0';
        }
        if (in_array($category, ['both', 'non_indigene'], true)) {
            $rules['non_indigene_amount'] = 'required|numeric|min:0';
        }

        $validated = $request->validate($rules);
        $validated['portal_charge'] = (float) ($validated['portal_charge'] ?? 0);
        $validated['amount'] = $this->resolveLegacyAmount($validated, $category);

        $fee->update($validated);
        return redirect()->route('admin.fees.index')->with('success', 'Fee updated');
    }

    /**
     * Pick the Naira amount to store in the legacy `amount` column based
     * on the selected student category. Preserves back-compat with any
     * reporting that still reads `fees.amount` directly.
     */
    private function resolveLegacyAmount(array $validated, string $category): float
    {
        return match ($category) {
            'indigene'      => (float) ($validated['indigene_amount'] ?? 0),
            'non_indigene'  => (float) ($validated['non_indigene_amount'] ?? 0),
            'portal_charge' => (float) ($validated['portal_charge'] ?? 0),
            default         => (float) ($validated['indigene_amount'] ?? $validated['non_indigene_amount'] ?? 0),
        };
    }

    public function destroy(Fee $fee)
    {
        $this->requirePermission('admin.fees.manage');
        $fee->delete();
        return back()->with('success', 'Fee deleted');
    }
}