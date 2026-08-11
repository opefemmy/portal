<?php

namespace App\Events\Hospital;

use App\Models\Hospital\HospitalAdmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a matron / ward_manager binds a HospitalPatient to a bed and
 * opens a HospitalAdmission row.
 *
 * Listeners (e.g. SendBedAssignedNotification) translate the event into a
 * bell-ringer notification for the assigned doctor and the matron's
 * notifications feed.
 */
class BedAssigned
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $admissionId)
    {
    }

    /**
     * Convenience accessor — null if the admission has already been deleted.
     */
    public function admission(): ?HospitalAdmission
    {
        return HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])->find($this->admissionId);
    }
}