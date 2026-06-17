<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        // Personal Information
        'surname', 'first_name', 'middle_name', 'date_of_birth',
        'place_of_birth', 'gender', 'marital_status', 'nationality',
        'state_of_origin', 'lga', 'permanent_address', 'contact_address',
        'email', 'phone', 'passport',

        // Guardian Information
        'guardian_name', 'guardian_relationship', 'guardian_phone',
        'guardian_email', 'guardian_occupation', 'guardian_address',

        // Educational Background
        'primary_school', 'primary_school_start', 'primary_school_end',
        'secondary_school', 'secondary_school_start', 'secondary_school_end',
        'tertiary_institution', 'tertiary_qualification', 'tertiary_start', 'tertiary_end',

        // Programme Selection
        'school_id', 'department_id', 'programme_id', 'mode_of_study', 'entry_level',

        // JAMB Details
        'jamb_registration_number', 'jamb_year', 'jamb_score',
        'jamb_subject1', 'jamb_subject2', 'jamb_subject3', 'jamb_subject4',

        // Documents
        'olevel_certificate', 'tertiary_certificate', 'birth_certificate',
        'lga_id', 'jamb_result',

        // Status
        'status', 'rejection_reason', 'reviewed_by', 'reviewed_at', 'user_id'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'jamb_score' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function programme()
    {
        return $this->belongsTo(Programme::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
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

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->surname}";
    }
}