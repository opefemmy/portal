<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PaymentTypeController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('payment_types')) {
            return view('admin.payment-types.index', ['paymentTypes' => collect([])]);
        }

        $paymentTypes = PaymentType::orderBy('priority')->get();
        return view('admin.payment-types.index', compact('paymentTypes'));
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('payment_types')) {
            return back()->with('error', 'Payment types table does not exist. Please run migrations.');
        }

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
        if (!Schema::hasTable('payment_types')) {
            return back()->with('error', 'Payment types table does not exist.');
        }

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
        if (!Schema::hasTable('payment_types')) {
            return back()->with('error', 'Payment types table does not exist.');
        }

        $paymentType->delete();
        return back()->with('success', 'Payment type deleted successfully');
    }

    public function toggle(PaymentType $paymentType)
    {
        if (!Schema::hasTable('payment_types')) {
            return back()->with('error', 'Payment types table does not exist.');
        }

        $paymentType->update(['is_active' => !$paymentType->is_active]);
        return back()->with('success', 'Payment type status updated');
    }
}
