<?php

namespace App\Services\Hospital;

use App\Models\Hospital\ExternalPatient;
use App\Models\Hospital\HospitalDrug;
use App\Models\Hospital\HospitalInventoryMovement;
use App\Models\Hospital\HospitalPatient;
use App\Models\Hospital\HospitalPayment;
use App\Models\Hospital\HospitalPrescription;
use App\Models\Hospital\HospitalPrescriptionItem;
use App\Models\Hospital\HospitalServiceRequest;
use App\Models\Hospital\HospitalServiceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Dispense pharmacy drugs automatically when a hospital payment is completed.
 *
 * Triggered by HospitalPaymentController whenever a payment transitions to 'completed'.
 *
 * Behaviour:
 *  1. Locate the HospitalServiceRequest linked to this payment.
 *  2. Locate the HospitalServiceType and its auto_dispense_drug_id (if any).
 *  3. Resolve the patient (HospitalPatient by phone, or ExternalPatient by phone/email).
 *  4. Create a HospitalPrescription, decrement drug stock via InventoryService,
 *     record inventory movement, and mark the prescription as dispensed.
 *
 * Failures (e.g. insufficient stock) are logged but never break the payment flow —
 * the prescription is still created in a 'pending' state for the pharmacist to resolve.
 */
class PharmacyAutoDispenseService
{
    public function __construct(protected InventoryService $inventory)
    {
    }

    public function dispenseOnPayment(HospitalPayment $payment): ?HospitalPrescription
    {
        // Only act once, on the transition from non-completed → completed.
        if ($payment->status !== HospitalPayment::STATUS_COMPLETED) {
            return null;
        }

        $serviceType = $payment->service_type_id
            ? HospitalServiceType::find($payment->service_type_id)
            : null;

        if (!$serviceType || !$serviceType->hasAutoDispense()) {
            Log::info('PharmacyAutoDispense: skipped — no auto-dispense drug mapped to service.', [
                'payment_id' => $payment->id,
                'service_type_id' => $payment->service_type_id,
            ]);
            return null;
        }

        $drug = HospitalDrug::find($serviceType->auto_dispense_drug_id);
        if (!$drug) {
            Log::warning('PharmacyAutoDispense: configured drug not found.', [
                'payment_id' => $payment->id,
                'drug_id' => $serviceType->auto_dispense_drug_id,
            ]);
            return null;
        }

        $patient = $this->resolvePatient($payment);
        if (!$patient) {
            Log::warning('PharmacyAutoDispense: patient not found for payment.', [
                'payment_id' => $payment->id,
                'phone' => $payment->patient_phone,
            ]);
            return null;
        }

        $quantity = max(1, (int) ($serviceType->auto_dispense_quantity ?? 1));

        return DB::transaction(function () use ($payment, $serviceType, $drug, $patient, $quantity) {
            $prescription = HospitalPrescription::create([
                'patient_id' => $patient->id,
                'doctor_id' => null,
                'medical_record_id' => null,
                'notes' => sprintf(
                    'Auto-dispensed for service "%s" (Payment: %s, Ref: %s).',
                    $serviceType->name,
                    $payment->id,
                    $payment->payment_ref
                ),
                'status' => 'pending',
            ]);

            $item = HospitalPrescriptionItem::create([
                'prescription_id' => $prescription->id,
                'drug_id' => $drug->id,
                'drug_name' => $drug->name,
                'dosage' => $drug->strength ?: '1 unit',
                'frequency' => 'Once',
                'duration' => 'Single dose',
                'quantity' => $quantity,
                'instructions' => 'Auto-dispensed on payment completion.',
                'is_dispensed' => false,
            ]);

            // Link the related service request to the prescription (best effort).
            HospitalServiceRequest::where('payment_id', $payment->id)
                ->update(['status' => 'completed']);

            $stockShort = $drug->current_stock < $quantity;

            if (!$stockShort) {
                try {
                    $this->inventory->dispense(
                        $drug,
                        $quantity,
                        sprintf(
                            'Auto-dispense — Payment %s, Prescription #%s',
                            $payment->payment_ref,
                            $prescription->id
                        )
                    );

                    $item->update(['is_dispensed' => true]);

                    $prescription->update([
                        'status' => 'dispensed',
                        'dispensed_by' => null,
                        'dispensed_at' => now(),
                    ]);

                    Log::info('PharmacyAutoDispense: drug dispensed.', [
                        'payment_id'       => $payment->id,
                        'prescription_id'  => $prescription->id,
                        'drug_id'          => $drug->id,
                        'quantity'         => $quantity,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('PharmacyAutoDispense: dispense failed, prescription left pending.', [
                        'payment_id'      => $payment->id,
                        'prescription_id' => $prescription->id,
                        'drug_id'         => $drug->id,
                        'error'           => $e->getMessage(),
                    ]);
                }
            } else {
                Log::warning('PharmacyAutoDispense: insufficient stock, prescription left pending.', [
                    'payment_id'      => $payment->id,
                    'prescription_id' => $prescription->id,
                    'drug_id'         => $drug->id,
                    'required'        => $quantity,
                    'available'       => $drug->current_stock,
                ]);
            }

            return $prescription->fresh();
        });
    }

    /**
     * Resolve the patient record from payment data.
     * Prefers HospitalPatient (internal) when a phone match exists; falls back to ExternalPatient.
     */
    protected function resolvePatient(HospitalPayment $payment): ?Model
    {
        if ($payment->patient_phone) {
            $internal = HospitalPatient::where('phone', $payment->patient_phone)->first();
            if ($internal) {
                return $internal;
            }

            $external = ExternalPatient::where('phone', $payment->patient_phone)->first();
            if ($external) {
                return $external;
            }
        }

        if ($payment->patient_email) {
            $internal = HospitalPatient::where('email', $payment->patient_email)->first();
            if ($internal) {
                return $internal;
            }

            $external = ExternalPatient::where('email', $payment->patient_email)->first();
            if ($external) {
                return $external;
            }
        }

        return null;
    }
}
