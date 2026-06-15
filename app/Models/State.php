<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = ['name', 'code'];

    public function localGovernments(): HasMany
    {
        return $this->hasMany(LocalGovernment::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}