<?php

namespace App\Models\Hospital;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class HospitalStaffNote extends Model
{
    use SoftDeletes;

    protected $table = 'hospital_staff_notes';

    protected $fillable = [
        'patient_id', 'author_id', 'appointment_id',
        'audience', 'note_type', 'body', 'is_pinned',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
    ];

    /**
     * Audiences the note is addressed to. Matches the role slugs the
     * HospitalPermissions catalogue already understands so we can
     * pivot the patient timeline around them.
     */
    public const AUDIENCES = [
        'all', 'doctor', 'nurse', 'pharmacy', 'lab',
        'records', 'radiology', 'reception',
    ];

    public const NOTE_TYPES = [
        'handover'   => 'Handover',
        'instruction'=> 'Instruction',
        'commentary' => 'Commentary',
        'alert'      => 'Alert',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(HospitalPatient::class, 'patient_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(HospitalAppointment::class, 'appointment_id');
    }
}
