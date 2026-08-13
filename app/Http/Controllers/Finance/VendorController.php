<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\Finance\FinanceVendor;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VendorController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('finance.vendors.view');

        $query = FinanceVendor::query();

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%");
            });
        }

        $vendors = $query->orderBy('name')->paginate(20);

        return view('finance.vendors.index', compact('vendors'));
    }

    public function create()
    {
        $this->requirePermission('finance.vendors.create');
        return view('finance.vendors.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission('finance.vendors.create');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:finance_vendors,code',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        FinanceVendor::create($request->all());

        return redirect()->route('finance.vendors.index')
            ->with('success', 'Vendor added successfully');
    }

    public function show(FinanceVendor $vendor)
    {
        $this->requirePermission('finance.vendors.view');

        $vendor->load(['purchaseOrders', 'payments']);

        return view('finance.vendors.show', compact('vendor'));
    }

    public function edit(FinanceVendor $vendor)
    {
        $this->requirePermission('finance.vendors.edit');
        return view('finance.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, FinanceVendor $vendor)
    {
        $this->requirePermission('finance.vendors.edit');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $vendor->update($request->all());

        return redirect()->route('finance.vendors.show', $vendor->id)
            ->with('success', 'Vendor updated successfully');
    }

    public function destroy(FinanceVendor $vendor)
    {
        $this->requirePermission('finance.vendors.edit');

        $vendor->delete();

        return redirect()->route('finance.vendors.index')
            ->with('success', 'Vendor deleted successfully');
    }
}