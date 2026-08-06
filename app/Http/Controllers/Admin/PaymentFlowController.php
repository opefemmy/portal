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
 * Drives every row from the database catalogue: every PaymentType row
 * that exists for the applicant audience (or `both`) shows up here
 * with its default amount + override field + active toggle. The legacy
 * "three-purpose" hardcoded list (PURPOSES/LABELS/CODES) is gone —
 * admins add payment types at /admin/payment-types and they appear here
 * without code changes.
 */
class PaymentFlowController extends Controller
{
    public function edit(ApplicantPaymentService $payments)
    {
        // Pull every applicant-audience PaymentType row, regardless of
        // purpose. sort by priority then name so the form has the same
        // ordering as the applicant dashboard.
        $types = $payments->getApplicantPaymentTypes();

        $rows = $types->map(function (PaymentType $type) use ($payments): array {
            return [
                'purpose' => $type->purpose,
                'label' => $type->display_label,
                'code' => $type->code,
                'type' => $type,
                'defaultAmount' => (float) $type->amount,
                'isActive' => (bool) $type->is_active,
                'overrideKey' => $payments::OVERRIDE_KEYS_PUBLIC[$type->purpose] ?? null,
                'overrideAmount' => $this->overrideAmount($type->purpose),
            ];
        })->values()->all();

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
            'overrides.*' => 'nullable|numeric|min:0',
            'is_active' => 'array',
            'form_open' => 'nullable|boolean',
            'require_fee' => 'nullable|boolean',
        ]);

        // Live overrides: each payment type has its own override key,
        // pulled from the service's OVERRIDE_KEYS_PUBLIC map. Types
        // outside that map (admin-created extras) just use the catalogue
        // default amount — no override needed.
        foreach ($payments->getApplicantPaymentTypes() as $type) {
            $overrideKey = $payments::OVERRIDE_KEYS_PUBLIC[$type->purpose] ?? null;
            if (! $overrideKey) {
                continue;
            }
            $value = $validated['overrides'][$type->purpose] ?? null;
            SystemSetting::set(
                $overrideKey,
                $value === null || $value === '' ? '' : (string) $value
            );
        }

        // Per-type is_active toggle. Admin can flip any catalogue row on/off.
        foreach ($payments->getApplicantPaymentTypes() as $type) {
            $active = $request->boolean('is_active.' . $type->purpose, $type->is_active);
            if ($active !== (bool) $type->is_active) {
                $type->update(['is_active' => $active]);
            }
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
