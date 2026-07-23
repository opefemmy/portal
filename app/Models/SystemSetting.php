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
    const ADMISSION_REQUIRE_FEE = 'admission_require_application_fee';
    const ADMISSION_FEE_AMOUNT = 'admission_application_fee_amount';
    const COURSE_REGISTRATION_OPEN = 'course_registration_open';
    const COURSE_REGISTRATION_PENALTY = 'course_registration_penalty';
    const PAYMENT_OPEN = 'payment_open';
    const PAYMENT_PENALTY = 'payment_penalty';
    const RESULT_UPLOAD_OPEN = 'result_upload_open';

    // Institution Branding Settings
    const INSTITUTION_NAME = 'institution_name';
    const INSTITUTION_SHORT_NAME = 'institution_short_name';
    const INSTITUTION_LOGO = 'institution_logo';
    const INSTITUTION_ICON = 'institution_icon';
    const INSTITUTION_ADDRESS = 'institution_address';
    const INSTITUTION_PHONE = 'institution_phone';
    const INSTITUTION_EMAIL = 'institution_email';
    const INSTITUTION_WEBSITE = 'institution_website';
    const INSTITUTION_TAGLINE = 'institution_tagline';

    public static function isOpen($key)
    {
        return (bool) static::get($key, 'false');
    }

    public static function getPenalty($key)
    {
        return static::get($key . '_penalty', 0);
    }

    /**
     * Set a setting value (static method)
     */
    public static function set($key, $value)
    {
        return static::updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    /**
     * Get a setting value (static method)
     */
    public static function get($key, $default = null)
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Get institution name
     */
    public static function getInstitutionName()
    {
        return static::get(static::INSTITUTION_NAME, 'Ekiti State College of Technology');
    }

    /**
     * Get institution short name
     */
    public static function getInstitutionShortName()
    {
        return static::get(static::INSTITUTION_SHORT_NAME, 'EKSCOTECH');
    }

    /**
     * Get institution logo URL
     */
    public static function getInstitutionLogo()
    {
        return static::get(static::INSTITUTION_LOGO, null);
    }

    /**
     * Get institution icon URL
     */
    public static function getInstitutionIcon()
    {
        return static::get(static::INSTITUTION_ICON, null);
    }

    /**
     * Check if admission form requires payment
     */
    public static function requiresAdmissionFee()
    {
        return static::get(static::ADMISSION_REQUIRE_FEE, 'false') === 'true';
    }

    /**
     * Get admission fee amount
     */
    public static function getAdmissionFeeAmount()
    {
        return (float) static::get(static::ADMISSION_FEE_AMOUNT, 0);
    }
}