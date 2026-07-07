<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    protected $fillable = ['min_score', 'max_score', 'grade', 'grade_point', 'remark', 'classification', 'gpa_weight'];

    protected $casts = [
        'min_score' => 'integer',
        'max_score' => 'integer',
        'grade_point' => 'decimal:1',
        'gpa_weight' => 'integer',
    ];

    public static function getGrade($score)
    {
        return static::where('min_score', '<=', $score)
            ->where('max_score', '>=', $score)
            ->first();
    }

    /**
     * Get the classification based on GPA
     */
    public static function getClassification($gpa, $programmeType = 'ND')
    {
        $classifications = [
            'first_class' => ['min' => 4.5, 'label' => 'First Class Honours'],
            'second_class_upper' => ['min' => 3.5, 'label' => 'Second Class Upper'],
            'second_class_lower' => ['min' => 2.5, 'label' => 'Second Class Lower'],
            'third_class' => ['min' => 1.5, 'label' => 'Third Class'],
            'pass' => ['min' => 1.0, 'label' => 'Pass'],
            'fail' => ['min' => 0, 'label' => 'Fail'],
        ];

        foreach ($classifications as $key => $class) {
            if ($gpa >= $class['min']) {
                return (object)['key' => $key, 'label' => $class['label']];
            }
        }

        return (object)['key' => 'fail', 'label' => 'Fail'];
    }

    public static function getDefaultGrades()
    {
        return [
            ['min_score' => 70, 'max_score' => 100, 'grade' => 'A', 'grade_point' => 5.0, 'remark' => 'Excellent', 'classification' => 'first_class', 'gpa_weight' => 5],
            ['min_score' => 60, 'max_score' => 69, 'grade' => 'B', 'grade_point' => 4.0, 'remark' => 'Very Good', 'classification' => 'second_class_upper', 'gpa_weight' => 4],
            ['min_score' => 50, 'max_score' => 59, 'grade' => 'C', 'grade_point' => 3.0, 'remark' => 'Good', 'classification' => 'second_class_lower', 'gpa_weight' => 3],
            ['min_score' => 45, 'max_score' => 49, 'grade' => 'D', 'grade_point' => 2.0, 'remark' => 'Pass', 'classification' => 'third_class', 'gpa_weight' => 2],
            ['min_score' => 40, 'max_score' => 44, 'grade' => 'E', 'grade_point' => 1.0, 'remark' => 'Fair', 'classification' => 'pass', 'gpa_weight' => 1],
            ['min_score' => 0, 'max_score' => 39, 'grade' => 'F', 'grade_point' => 0.0, 'remark' => 'Fail', 'classification' => 'fail', 'gpa_weight' => 0],
        ];
    }
}