<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Applicant extends Model
{
    protected $fillable = [
        // Personal Information
        'user_id', 'application_number', 'surname', 'first_name', 'middle_name',
        'date_of_birth', 'place_of_birth', 'gender', 'marital_status',
        'nationality', 'state_of_origin', 'lga', 'permanent_address',
        'contact_address', 'email', 'phone', 'passport',

        // Guardian Information
        'guardian_name', 'guardian_relationship', 'guardian_phone',
        'guardian_email', 'guardian_occupation', 'guardian_address',

        // Educational Background
        'primary_school', 'primary_school_start', 'primary_school_end',
        'secondary_school', 'secondary_school_start', 'secondary_school_end',
        'tertiary_institution', 'tertiary_qualification', 'tertiary_start', 'tertiary_end',

        // Programme Selection
        'school_id', 'department_id', 'programme_id', 'session_id',
        'mode_of_study', 'entry_level',

        // JAMB Details
        'jamb_registration_number', 'jamb_year', 'jamb_score',
        'jamb_subject1', 'jamb_subject2', 'jamb_subject3', 'jamb_subject4',

        // Documents
        'olevel_certificate', 'tertiary_certificate', 'birth_certificate',
        'lga_id', 'jamb_result',

        // Payment
        'payment_status', 'payment_ref', 'payment_transaction_id',
        'payment_amount', 'payment_date', 'application_fee_id',

        // Status
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'jamb_score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function programme(): BelongsTo
    {
        return $this->belongsTo(Programme::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public static function generateApplicationNumber()
    {
        return 'APP-' . strtoupper(Str::random(8));
    }

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->surname}";
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScreening($query)
    {
        return $query->where('status', 'screening');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeAdmitted($query)
    {
        return $query->where('status', 'admitted');
    }
}