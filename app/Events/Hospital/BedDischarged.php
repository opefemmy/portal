<?php

namespace App\Events\Hospital;

use App\Models\Hospital\HospitalAdmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a matron / ward_manager discharges a patient from a bed and
 * closes the corresponding HospitalAdmission row.
 *
 * Listeners notify the doctor, update ward occupancy feeds, and push a
 * notification to the patient-portal feed when the patient is also an
 * external portal user.
 */
class BedDischarged
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $admissionId)
    {
    }

    public function admission(): ?HospitalAdmission
    {
        return HospitalAdmission::with(['patient', 'bed.ward', 'doctor'])->find($this->admissionId);
    }
}