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
        'religion', 'blood_group', 'genotype', 'disability', 'disability_details',
        'address', 'state_id', 'nationality_id',

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

        // O-Level Results
        'olevel1_subject1', 'olevel1_grade1', 'olevel1_subject2', 'olevel1_grade2',
        'olevel1_subject3', 'olevel1_grade3', 'olevel1_subject4', 'olevel1_grade4',
        'olevel1_subject5', 'olevel1_grade5', 'olevel1_exam_year',
        'olevel1_exam_type', 'olevel1_exam_number',
        'olevel2_subject1', 'olevel2_grade1', 'olevel2_subject2', 'olevel2_grade2',
        'olevel2_subject3', 'olevel2_grade3', 'olevel2_subject4', 'olevel2_grade4',
        'olevel2_subject5', 'olevel2_grade5', 'olevel2_exam_year',
        'olevel2_exam_type', 'olevel2_exam_number',

        // Extra Curricular
        'extra_curricular',

        // Documents
        'olevel_certificate', 'tertiary_certificate', 'birth_certificate',
        'lga_id', 'jamb_result',

        // Payment
        'payment_status', 'payment_ref', 'payment_transaction_id',
        'payment_amount', 'payment_date', 'application_fee_id',

        // Status
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'matric_number'
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

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function lga(): BelongsTo
    {
        return $this->belongsTo(LocalGovernment::class, 'lga_id');
    }

    public function nationality(): BelongsTo
    {
        return $this->belongsTo(Nationality::class);
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

    /**
     * Determine if applicant is an indigene (from Ekiti state)
     */
    public function getCategoryAttribute(): string
    {
        // Ekiti state is considered indigene, all other states are non-indigene
        $ekitiKeywords = ['ekiti', 'ekiti state'];

        $state = strtolower($this->state_of_origin ?? '');

        foreach ($ekitiKeywords as $keyword) {
            if (str_contains($state, $keyword)) {
                return 'indigene';
            }
        }

        return 'non_indigene';
    }

    /**
     * Check if applicant is an indigene
     */
    public function isIndigene(): bool
    {
        return $this->category === 'indigene';
    }
}