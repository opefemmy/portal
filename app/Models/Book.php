<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = ['isbn', 'title', 'author', 'publisher', 'year', 'category', 'quantity', 'available', 'shelf_location', 'is_active'];

    protected $casts = [
        'quantity' => 'integer',
        'available' => 'integer',
        'is_active' => 'boolean',
    ];

    public function loans(): HasMany
    {
        return $this->hasMany(BookLoan::class);
    }

    public function isAvailable()
    {
        return $this->available > 0;
    }
}