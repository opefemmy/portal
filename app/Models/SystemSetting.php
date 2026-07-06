<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Keys for common settings
    const ADMISSION_FORM_OPEN = 'admission_form_open';
    const ADMISSION_FORM_PENALTY = 'admission_form_penalty';
    const COURSE_REGISTRATION_OPEN = 'course_registration_open';
    const COURSE_REGISTRATION_PENALTY = 'course_registration_penalty';
    const PAYMENT_OPEN = 'payment_open';
    const PAYMENT_PENALTY = 'payment_penalty';
    const RESULT_UPLOAD_OPEN = 'result_upload_open';

    public static function isOpen($key)
    {
        return (bool) static::get($key, 'false');
    }

    public static function getPenalty($key)
    {
        return static::get($key . '_penalty', 0);
    }
}