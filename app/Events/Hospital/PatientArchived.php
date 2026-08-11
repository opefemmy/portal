<?php

namespace App\Events\Hospital;

use App\Models\Hospital\HospitalPatient;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a medical_records_officer archives a patient chart.
 *
 * Listeners notify the requestor (if the patient had outstanding record
 * requests) and write a confirmation notification to the records officer's
 * own feed.
 */
class PatientArchived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public int $patientId)
    {
    }

    public function patient(): ?HospitalPatient
    {
        return HospitalPatient::find($this->patientId);
    }
}