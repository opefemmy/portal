<?php

namespace App\Services\Hospital;

use App\Models\Hospital\HospitalAuditTrail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Records hospital actions for compliance and clinical governance.
 *
 * Used by:
 *  - Hospital controllers (call AuditTrail::record(...) after a write op)
 *  - Job/event listeners for asynchronous trails
 *
 * Lightweight enough to call inline; writes are fire-and-forget
 * because the audit log MUST NOT block a clinical operation.
 */
class AuditTrail
{
    public static function record(
        string $action,
        ?Model $subject = null,
        ?int $patientId = null,
        array $before = [],
        array $after = [],
        array $metadata = []
    ): void {
        try {
            HospitalAuditTrail::create([
                'user_id'      => Auth::id(),
                'user_role'    => optional(Auth::user()?->role)->slug,
                'patient_id'   => $patientId,
                'action'       => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'ip_address'   => Request::ip(),
                'user_agent'   => substr((string) Request::userAgent(), 0, 250),
                'before'       => $before ?: null,
                'after'        => $after ?: null,
                'metadata'     => $metadata ?: null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit failures break a clinical action.
            \Log::warning('Audit trail write failed: ' . $e->getMessage());
        }
    }

    /**
     * Recent actions for a patient — used by the patient timeline view.
     */
    public static function forPatient(int $patientId, int $limit = 50)
    {
        return HospitalAuditTrail::with('user')
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
}