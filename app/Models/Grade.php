<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['min_score', 'max_score', 'grade', 'grade_point', 'remark'];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'grade_point' => 'decimal:1',
    ];

    public static function getGrade($score)
    {
        return static::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }

    public static function getDefaultGrades()
    {
        return [
            ['min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'grade_point' => 5.0, 'remark' => 'Excellent'],
            ['min_score' => 60, 'max_score' => 69, 'grade' => 'B', 'grade_point' => 4.0, 'remark' => 'Very Good'],
            ['min_score' => 50, 'max_score' => 59, 'grade' => 'C', 'grade_point' => 3.0, 'remark' => 'Good'],
            ['min_score' => 45, 'max_score' => 49, 'grade' => 'D', 'grade_point' => 2.0, 'remark' => 'Pass'],
            ['min_score' => 40, 'max_score' => 44, 'grade' => 'E', 'grade_point' => 1.0, 'remark' => 'Fair'],
            ['min_score' => 0, 'max_score' => 39, 'grade' => 'F', 'grade_point' => 0.0, 'remark' => 'Fail'],
        ];
    }
}