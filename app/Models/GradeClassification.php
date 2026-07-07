<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradeClassification extends Model
{
    protected $fillable = ['name', 'slug', 'min_gpa', 'max_gpa', 'description', 'sort_order'];

    protected $casts = [
        'min_gpa' => 'decimal:2',
        'max_gpa' => 'decimal:2',
    ];
}