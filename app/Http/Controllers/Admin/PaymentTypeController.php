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

    public function create()
    {
        $purposes = PaymentType::getPurposes();
        return view('admin.payment-types.create', compact('purposes'));
    }

    public function store(Request $request)
    {
        if (!Schema::hasTable('payment_types')) {
            return back()->with('error', 'Payment types table does not exist. Please run migrations.');
        }

        // Normalise the code to uppercase so 'Comp_Fee' and
        // 'comp_fee' collide on the unique index. This also means
        // the row stored on disk is always 'COMP_FEE', which keeps
        // the rest of the codebase (case-sensitive lookups in
        // ApplicantPaymentService, etc.) happy.
        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    \Illuminate\Validation\Rule::unique('payment_types', 'code'),
                ],
                'description' => 'nullable|string|max:500',
                'amount' => 'required|numeric|min:0',
                // Purpose is free-form so admins can add any new fee
                // (e.g. "compulsory_fee", "convocation") without a code
                // change. Existing enum-style values still work.
                'purpose' => 'nullable|string|max:50',
                // Audience is required, but we default to 'both' so the
                // form never silently bounces an admin back on a missing
                // dropdown value.
                'audience' => 'nullable|in:' . PaymentType::AUDIENCE_APPLICANT . ',' . PaymentType::AUDIENCE_STUDENT . ',' . PaymentType::AUDIENCE_BOTH,
                'is_active' => 'boolean',
                'requires_payment' => 'boolean',
                'payment_channel' => 'nullable|in:external,internal,both',
                'priority' => 'nullable|integer|min:1',
            ], [
                'name.required'   => 'Please give this payment type a name.',
                'code.required'   => 'Please enter a short code (e.g. COMP_FEE).',
                'code.unique'     => 'A payment type with this code already exists. Try a different code (e.g. COMP_FEE_2026).',
                'code.max'        => 'Code must be 50 characters or fewer.',
                'amount.required' => 'Please enter the amount to charge.',
                'amount.numeric'  => 'Amount must be a number.',
                'amount.min'      => 'Amount cannot be negative.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log the actual failing field/error so we can see it in
            // laravel.log if the user reports another bounce-back —
            // validate() by default just redirects with withErrors
            // and does NOT log.
            \Illuminate\Support\Facades\Log::warning('admin/payment-types: validation failed', [
                'action' => 'store',
                'errors' => $e->errors(),
                'input'  => $request->only(['name', 'code', 'amount', 'audience', 'purpose', 'payment_channel', 'priority']),
            ]);
            throw $e;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['requires_payment'] = $request->boolean('requires_payment');
        $validated['audience'] = $validated['audience'] ?? PaymentType::AUDIENCE_BOTH;
        $validated['payment_channel'] = $validated['payment_channel'] ?? 'both';
        $validated['priority'] = $validated['priority'] ?? 1;
        // Normalise free-text purpose to a safe slug-like value so
        // downstream enum-aware lookups (e.g. ApplicantPaymentService
        // -> feeTypeFor) keep working when an admin types something
        // like "Compulsory Fee" or "Convocation".
        if (!empty($validated['purpose'])) {
            $validated['purpose'] = strtolower(trim(preg_replace('/\s+/', '_', $validated['purpose'])));
        }

        // The 2026_07_24 migration added `purpose` and the 2026_08_04
        // migration added `audience`. On deployments that have not yet
        // run those migrations, the columns don't exist on
        // payment_types — without this guard, the INSERT would throw
        // "table payment_types has no column named audience" and the
        // user would see a 500 with no actionable message. Drop the
        // unsupported keys from the payload so the row still saves on
        // legacy schemas, and surface a one-time hint to run
        // migrations.
        $columnsToStrip = [];
        if (! Schema::hasColumn('payment_types', 'purpose')) {
            $columnsToStrip[] = 'purpose';
        }
        if (! Schema::hasColumn('payment_types', 'audience')) {
            $columnsToStrip[] = 'audience';
        }
        if (! Schema::hasColumn('payment_types', 'payment_channel')) {
            $columnsToStrip[] = 'payment_channel';
        }
        if (! Schema::hasColumn('payment_types', 'priority')) {
            $columnsToStrip[] = 'priority';
        }
        if (! Schema::hasColumn('payment_types', 'description')) {
            $columnsToStrip[] = 'description';
        }
        if (! Schema::hasColumn('payment_types', 'requires_payment')) {
            $columnsToStrip[] = 'requires_payment';
        }
        $payload = array_diff_key($validated, array_flip($columnsToStrip));

        try {
            PaymentType::create($payload);
        } catch (\Throwable $e) {
            // Final safety net: surface a clear flash instead of a 500
            // if some other column / constraint mismatch trips the
            // INSERT. The trace is logged for support.
            \Illuminate\Support\Facades\Log::error('admin/payment-types: create failed', [
                'payload_keys' => array_keys($payload),
                'stripped_keys' => $columnsToStrip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Could not save the payment type. Please run `php artisan migrate` if your database is missing recent columns, then try again.');
        }

        // If we had to strip columns, also remind the admin to run
        // migrations so they get the full feature set (audience
        // scoping etc.) on next save.
        $migrationHint = empty($columnsToStrip)
            ? null
            : ' The database is missing some columns — run `php artisan migrate` to enable audience scoping and the full feature set.';

        return redirect()
            ->route('admin.payment-types.index')
            ->with(
                'success',
                'Payment type created successfully.' . ($migrationHint ?? '')
            );
    }

    public function edit(PaymentType $paymentType)
    {
        $purposes = PaymentType::getPurposes();
        return view('admin.payment-types.edit', compact('paymentType', 'purposes'));
    }

    public function update(Request $request, PaymentType $paymentType)
    {
        if (!Schema::hasTable('payment_types')) {
            return back()->with('error', 'Payment types table does not exist.');
        }

        if ($request->filled('code')) {
            $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);
        }

        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'code' => [
                    'required',
                    'string',
                    'max:50',
                    \Illuminate\Validation\Rule::unique('payment_types', 'code')
                        ->ignore($paymentType->id),
                ],
                'description' => 'nullable|string|max:500',
                'amount' => 'required|numeric|min:0',
                'purpose' => 'nullable|string|max:50',
                'audience' => 'nullable|in:' . PaymentType::AUDIENCE_APPLICANT . ',' . PaymentType::AUDIENCE_STUDENT . ',' . PaymentType::AUDIENCE_BOTH,
                'is_active' => 'boolean',
                'requires_payment' => 'boolean',
                'payment_channel' => 'nullable|in:external,internal,both',
                'priority' => 'nullable|integer|min:1',
            ], [
                'name.required'   => 'Please give this payment type a name.',
                'code.required'   => 'Please enter a short code (e.g. COMP_FEE).',
                'code.unique'     => 'A payment type with this code already exists. Try a different code (e.g. COMP_FEE_2026).',
                'code.max'        => 'Code must be 50 characters or fewer.',
                'amount.required' => 'Please enter the amount to charge.',
                'amount.numeric'  => 'Amount must be a number.',
                'amount.min'      => 'Amount cannot be negative.',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Illuminate\Support\Facades\Log::warning('admin/payment-types: validation failed', [
                'action' => 'update',
                'id'     => $paymentType->id,
                'errors' => $e->errors(),
                'input'  => $request->only(['name', 'code', 'amount', 'audience', 'purpose', 'payment_channel', 'priority']),
            ]);
            throw $e;
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['requires_payment'] = $request->boolean('requires_payment');
        $validated['audience'] = $validated['audience'] ?? PaymentType::AUDIENCE_BOTH;
        $validated['payment_channel'] = $validated['payment_channel'] ?? 'both';
        $validated['priority'] = $validated['priority'] ?? 1;
        if (!empty($validated['purpose'])) {
            $validated['purpose'] = strtolower(trim(preg_replace('/\s+/', '_', $validated['purpose'])));
        }

        // Strip columns that don't exist on this deployment so the
        // update doesn't 500 on unrun migrations. See store() for the
        // same guard and the full rationale.
        $columnsToStrip = [];
        if (! Schema::hasColumn('payment_types', 'purpose')) {
            $columnsToStrip[] = 'purpose';
        }
        if (! Schema::hasColumn('payment_types', 'audience')) {
            $columnsToStrip[] = 'audience';
        }
        if (! Schema::hasColumn('payment_types', 'payment_channel')) {
            $columnsToStrip[] = 'payment_channel';
        }
        if (! Schema::hasColumn('payment_types', 'priority')) {
            $columnsToStrip[] = 'priority';
        }
        if (! Schema::hasColumn('payment_types', 'description')) {
            $columnsToStrip[] = 'description';
        }
        if (! Schema::hasColumn('payment_types', 'requires_payment')) {
            $columnsToStrip[] = 'requires_payment';
        }
        $payload = array_diff_key($validated, array_flip($columnsToStrip));

        try {
            $paymentType->update($payload);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('admin/payment-types: update failed', [
                'id' => $paymentType->id,
                'payload_keys' => array_keys($payload),
                'stripped_keys' => $columnsToStrip,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Could not update the payment type. Please run `php artisan migrate` if your database is missing recent columns, then try again.');
        }

        $migrationHint = empty($columnsToStrip)
            ? null
            : ' The database is missing some columns — run `php artisan migrate` to enable audience scoping and the full feature set.';

        return redirect()
            ->route('admin.payment-types.index')
            ->with(
                'success',
                'Payment type updated successfully.' . ($migrationHint ?? '')
            );
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
