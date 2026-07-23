<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Semester extends Model
{
    protected $fillable = ['name', 'code', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function studentCourses(): HasMany
    {
        return $this->hasMany(StudentCourse::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(Result::class);
    }

    public function scopeActive($query)
    {
        if (!Schema::hasTable('semesters')) {
            return $query->whereRaw('1=0');
        }
        return $query->where('is_active', true);
    }

    public static function getCurrentSemester()
    {
        if (!Schema::hasTable('semesters')) {
            return null;
        }
        return static::where('is_active', true)->orderBy('sort_order')->first();
    }
}
