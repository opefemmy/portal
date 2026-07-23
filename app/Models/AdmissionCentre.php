<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        return $this->hasMany(Applicant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
