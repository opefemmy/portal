<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;

class PaymentTypeController extends Controller
{
    public function index()
    {
        $paymentTypes = PaymentType::orderBy('priority')->get();
        return view('admin.payment-types.index', compact('paymentTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_types,code',
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'requires_payment' => 'boolean',
            'payment_channel' => 'required|in:external,internal,both',
            'priority' => 'nullable|integer|min:1',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['requires_payment'] = $request->has('requires_payment');

        PaymentType::create($validated);
        return back()->with('success', 'Payment type created successfully');
    }

    public function update(Request $request, PaymentType $paymentType)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:payment_types,code,' . $paymentType->id,
            'description' => 'nullable|string|max:500',
            'amount' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'requires_payment' => 'boolean',
            'payment_channel' => 'required|in:external,internal,both',
            'priority' => 'nullable|integer|min:1',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['requires_payment'] = $request->has('requires_payment');

        $paymentType->update($validated);
        return back()->with('success', 'Payment type updated successfully');
    }

    public function destroy(PaymentType $paymentType)
    {
        $paymentType->delete();
        return back()->with('success', 'Payment type deleted successfully');
    }

    public function toggle(PaymentType $paymentType)
    {
        $paymentType->update(['is_active' => !$paymentType->is_active]);
        return back()->with('success', 'Payment type status updated');
    }
}
