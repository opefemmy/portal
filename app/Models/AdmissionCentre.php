<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class AdmissionCentre extends Model
{
    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function applicants(): HasMany
    {
        // applicants.centre_id is the actual FK column added by
        // migration 2026_07_23_000002 — using 'admission_centre_id'
        // here caused AdmissionCentreController::index() to fail
        // with `Unknown column 'applicants.admission_centre_id'` from
        // the withCount('applicants') subquery. See plan:
        // "Multi-area Portal Updates — Part A".
        return $this->hasMany(Applicant::class, 'centre_id');
    }

    public function scopeActive($query)
    {
        if (!Schema::hasTable('admission_centres')) {
            return $query->whereRaw('1=0');
        }
        return $query->where('is_active', true);
    }
}
