<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentType;
use App\Models\SystemSetting;
use App\Services\ApplicantPaymentService;
use Illuminate\Http\Request;

/**
 * Combined admin screen for the admission payment flow.
 *
 * Lets an admin set the three amounts (application / acceptance / compulsory)
 * and the three "live" overrides, and toggle the form-open / require-fee
 * switches. Saves straight to PaymentType + SystemSetting; the
 * ApplicantPaymentService picks them up at request time.
 */
class PaymentFlowController extends Controller
{
    private const PURPOSES = [
        PaymentType::PURPOSE_APPLICATION,
        PaymentType::PURPOSE_ACCEPTANCE,
        PaymentType::PURPOSE_SCHOOL_FEE,
    ];

    private const LABELS = [
        PaymentType::PURPOSE_APPLICATION => 'Application Fee',
        PaymentType::PURPOSE_ACCEPTANCE => 'Acceptance Fee',
        PaymentType::PURPOSE_SCHOOL_FEE => 'Compulsory Fee',
    ];

    private const CODES = [
        PaymentType::PURPOSE_APPLICATION => 'APP_FORM',
        PaymentType::PURPOSE_ACCEPTANCE => 'ACCEPT_FEE',
        PaymentType::PURPOSE_SCHOOL_FEE => 'SCHOOL_FEE',
    ];

    public function edit(ApplicantPaymentService $payments)
    {
        $rows = [];
        foreach (self::PURPOSES as $purpose) {
            $type = $payments->resolvePaymentType($purpose);
            $rows[] = [
                'purpose' => $purpose,
                'label' => self::LABELS[$purpose],
                'code' => self::CODES[$purpose],
                'type' => $type,
                'defaultAmount' => $type ? (float) $type->amount : 0.0,
                'isActive' => $type ? (bool) $type->is_active : false,
                'overrideKey' => $payments::OVERRIDE_KEYS_PUBLIC[$purpose] ?? null,
                'overrideAmount' => $this->overrideAmount($purpose),
            ];
        }

        $formOpen = SystemSetting::isOpen(SystemSetting::ADMISSION_FORM_OPEN);
        $requireFee = SystemSetting::requiresAdmissionFee();

        return view('admin.admission.payment-flow', [
            'rows' => $rows,
            'formOpen' => $formOpen,
            'requireFee' => $requireFee,
        ]);
    }

    public function update(Request $request, ApplicantPaymentService $payments)
    {
        $validated = $request->validate([
            'overrides' => 'array',
            'overrides.application' => 'nullable|numeric|min:0',
            'overrides.acceptance' => 'nullable|numeric|min:0',
            'overrides.school_fee' => 'nullable|numeric|min:0',
            'is_active' => 'array',
            'form_open' => 'nullable|boolean',
            'require_fee' => 'nullable|boolean',
        ]);

        // Live overrides
        $overrideMap = [
            PaymentType::PURPOSE_APPLICATION => 'admission_application_fee_amount',
            PaymentType::PURPOSE_ACCEPTANCE => 'admission_accept_fee_amount',
            PaymentType::PURPOSE_SCHOOL_FEE => 'admission_school_fee_amount',
        ];
        foreach ($overrideMap as $purpose => $key) {
            $value = $validated['overrides'][$purpose] ?? null;
            // Empty string → clear the override.
            SystemSetting::set($key, $value === null || $value === '' ? '' : (string) $value);
        }

        // Per-purpose is_active
        foreach (self::PURPOSES as $purpose) {
            $type = $payments->resolvePaymentType($purpose);
            if (! $type) {
                continue;
            }
            $active = $request->boolean('is_active.' . $purpose, true);
            $type->update(['is_active' => $active]);
        }

        SystemSetting::set(SystemSetting::ADMISSION_FORM_OPEN, $request->boolean('form_open') ? 'true' : 'false');
        SystemSetting::set(SystemSetting::ADMISSION_REQUIRE_FEE, $request->boolean('require_fee') ? 'true' : 'false');

        return redirect()->route('admin.admission.payment-flow')
            ->with('success', 'Admission payment flow updated.');
    }

    private function overrideAmount(string $purpose): float
    {
        $key = ApplicantPaymentService::OVERRIDE_KEYS_PUBLIC[$purpose] ?? null;
        if (! $key) {
            return 0.0;
        }

        return (float) SystemSetting::get($key, 0);
    }
}
