<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    protected $guarded = ['id'];

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'passport', 'gender',
        'date_of_birth', 'phone', 'address', 'state', 'lga',
        'next_of_kin', 'next_of_kin_phone', 'matric_number', 'staff_id',
        'school_id', 'department_id', 'programme_id', 'level',
        'two_factor_secret', 'is_active', 'must_change_password',
        'unlock_code', 'unlock_code_expires_at', 'password_changed_at'
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
            'password' => 'hashed',
            'unlock_code_expires_at' => 'datetime',
            'password_changed_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * All roles attached to this user via the `role_user` pivot.
     *
     * `users.role_id` continues to be the **primary** role — used by
     * RoleMiddleware, LoginController, and AuthService for redirect
     * gating. This relation exposes *additional* roles so a single
     * user can hold multiple roles (e.g. `bursar` + `cashier`) without
     * losing the primary-role redirect path.
     *
     * Use `sync()` / `attach()` / `detach()` on this relation to
     * manage memberships. The `booted()` hook below keeps the pivot
     * in sync when `role_id` is changed elsewhere.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user')
            ->withPivot('created_at');
    }

    /**
     * All role slugs attached to this user — primary first, then the
     * pivot. Deduplicated. Useful for gate checks that previously
     * only consulted the primary role's slug.
     */
    public function allRoleSlugs(): array
    {
        $slugs = [];
        if ($this->role) {
            $slugs[] = $this->role->slug;
        }
        foreach ($this->roles as $r) {
            $slugs[] = $r->slug;
        }
        return array_values(array_unique($slugs));
    }

    /**
     * Check if this user holds any of the given role slugs across
     * the primary role and the pivot.
     */
    public function hasAnyRoleSlug(array $slugs): bool
    {
        return (bool) array_intersect($slugs, $this->allRoleSlugs());
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

    public function hospitalStaff(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Hospital\HospitalStaff::class);
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

    /**
     * Get the department if user is HOD
     */
    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    /**
     * Get the school if user is Dean
     */
    public function getSchool(): ?School
    {
        return $this->school;
    }

    /**
     * Scope to filter users by HOD role
     */
    public function scopeHod($query)
    {
        return $query->whereHas('role', function($q) {
            $q->where('slug', 'hod');
        });
    }

    /**
     * Scope to filter users by Dean role
     */
    public function scopeDean($query)
    {
        return $query->whereHas('role', function($q) {
            $q->where('slug', 'dean');
        });
    }

    /**
     * Scope to filter users by Lecturer role
     */
    public function scopeLecturer($query)
    {
        return $query->whereHas('role', function($q) {
            $q->where('slug', 'lecturer');
        });
    }

    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    /**
     * Determine if user is an indigene (from Ekiti state).
     * Delegated to IndigeneResolver so the keyword list stays in one place.
     */
    public function getCategoryAttribute(): string
    {
        return \App\Services\IndigeneResolver::categoryFor($this);
    }

    /**
     * Check if user is an indigene.
     */
    public function isIndigene(): bool
    {
        return \App\Services\IndigeneResolver::isIndigene($this);
    }

    /**
     * Send the email verification notification.
     * Customized for the institution portal.
     */
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role && $this->role->slug === $role;
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->role && in_array($this->role->slug, $roles);
    }

    /**
     * Get the user's full name with title
     */
    public function getFullNameAttribute(): string
    {
        return $this->name;
    }

    /**
     * Get avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->passport) {
            // Check both possible locations for backward compatibility
            $publicPath = public_path('uploads/passports/' . $this->passport);
            $storagePath = public_path('storage/passports/' . $this->passport);

            if (file_exists($publicPath)) {
                return asset('uploads/passports/' . $this->passport);
            } elseif (file_exists($storagePath)) {
                return asset('storage/passports/' . $this->passport);
            }
        }

        // Generate avatar based on initials
        $initials = strtoupper(substr($this->name, 0, 2));
        return "https://ui-avatars.com/api/?name={$initials}&background=1a237e&color=fff";
    }

    /**
     * Check if password needs to be changed
     */
    public function getMustChangePasswordAttribute(): bool
    {
        return $this->attributes['must_change_password'] ?? false;
    }

    /**
     * Generate a unique unlock code for admin password reset
     */
    public function generateUnlockCode(): string
    {
        $code = strtoupper(Str::random(8));
        $this->update([
            'unlock_code' => Hash::make($code),
            'unlock_code_expires_at' => now()->addHours(24),
        ]);
        return $code;
    }

    /**
     * Validate unlock code
     */
    public function validateUnlockCode(string $code): bool
    {
        if (!$this->unlock_code || !$this->unlock_code_expires_at) {
            return false;
        }

        if (now()->greaterThan($this->unlock_code_expires_at)) {
            return false;
        }

        return Hash::check($code, $this->unlock_code);
    }

    /**
     * Clear unlock code after use
     */
    public function clearUnlockCode(): void
    {
        $this->update([
            'unlock_code' => null,
            'unlock_code_expires_at' => null,
        ]);
    }

    /**
     * Unlock user password without knowing current password
     */
    public function unlockWithNewPassword(string $newPassword): bool
    {
        $this->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
            'must_change_password' => false,
            'unlock_code' => null,
            'unlock_code_expires_at' => null,
        ]);
        return true;
    }

    /**
     * Keep the `role_user` pivot in sync when `role_id` changes.
     *
     * Existing flows that update `users.role_id` (Admin\UserController,
     * bulk uploads, the staff importer, etc.) don't know about the
     * pivot. Without this hook, a user could end up with a primary
     * role that's not in the pivot — the index page would render the
     * primary pill but the checkbox wouldn't be pre-checked, which
     * would surprise the admin. `syncWithoutDetaching` only adds the
     * new role; existing memberships are preserved.
     */
    protected static function booted(): void
    {
        static::saved(function (User $user) {
            if ($user->wasChanged('role_id') && $user->role_id) {
                // The relation may not be loaded yet — load it
                // explicitly so syncWithoutDetaching reads the live
                // pivot, not a stale cached version.
                $user->load('roles');
                $user->roles()->syncWithoutDetaching([$user->role_id]);
            }
        });
    }
}