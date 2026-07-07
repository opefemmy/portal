<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GradingScale extends Model
{
    protected $fillable = ['grade', 'min_score', 'max_score', 'grade_point', 'gpa_weight', 'remark', 'classification', 'sort_order'];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'grade_point' => 'decimal:2',
        'gpa_weight' => 'decimal:2',
    ];
}