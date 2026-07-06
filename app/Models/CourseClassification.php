<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseClassification extends Model
{
    protected $fillable = ['course_id', 'type', 'category', 'priority'];

    const TYPE_MAIN = 'main';
    const TYPE_ELECTIVE = 'elective';
    const TYPE_CARRY_OVER = 'carry_over';

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}