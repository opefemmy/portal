<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    protected $fillable = ['name', 'semester', 'is_active', 'is_current', 'start_date', 'end_date'];

    protected $casts = [
        'is_active' => 'boolean',
        'is_current' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function applicants(): HasMany
    {
        return $this->hasMany(Applicant::class);
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function fees(): HasMany
    {
        return $this->hasMany(Fee::class);
    }

    public static function getCurrentSession()
    {
        return static::where('is_current', true)->first();
    }

    public static function setCurrentSession($id)
    {
        static::query()->update(['is_current' => false]);
        static::find($id)->update(['is_current' => true]);
    }
}