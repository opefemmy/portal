<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LocalGovernment extends Model
{
    protected $fillable = ['state_id', 'name', 'code'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}