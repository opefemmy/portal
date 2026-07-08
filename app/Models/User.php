<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'passport', 'gender',
        'date_of_birth', 'phone', 'address', 'state', 'lga',
        'next_of_kin', 'next_of_kin_phone', 'matric_number', 'staff_id',
        'school_id', 'department_id', 'programme_id', 'level',
        'two_factor_secret', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
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

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function courseAssignments(): HasMany
    {
        return $this->hasMany(CourseAssignment::class, 'lecturer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role && in_array($this->role->slug, ['super_admin', 'admin']);
    }

    public function isStudent(): bool
    {
        return $this->role && $this->role->slug === 'student';
    }

    public function isLecturer(): bool
    {
        return $this->role && $this->role->slug === 'lecturer';
    }

    public function isHOD(): bool
    {
        return $this->role && $this->role->slug === 'hod';
    }

    public function isDean(): bool
    {
        return $this->role && $this->role->slug === 'dean';
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Determine if user is an indigene (from Ekiti state)
     */
    public function getCategoryAttribute(): string
    {
        // Ekiti state is considered indigene, all other states are non-indigene
        $ekitiKeywords = ['ekiti', 'ekiti state'];

        $state = strtolower($this->state ?? '');

        foreach ($ekitiKeywords as $keyword) {
            if (str_contains($state, $keyword)) {
                return 'indigene';
            }
        }

        return 'non_indigene';
    }

    /**
     * Check if user is an indigene
     */
    public function isIndigene(): bool
    {
        return $this->category === 'indigene';
    }
}