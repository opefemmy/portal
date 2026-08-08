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

    // Payment Settings
    const PAYMENT_PORTAL_URL = 'payment_portal_url';

    // Institution Branding Settings
    const INSTITUTION_NAME = 'institution_name';
    const INSTITUTION_SHORT_NAME = 'institution_short_name';
    const INSTITUTION_LOGO = 'institution_logo';
    const INSTITUTION_ICON = 'institution_icon';
    const HOUSE_ICON = 'house_icon';
    const INSTITUTION_ADDRESS = 'institution_address';
    const INSTITUTION_PHONE = 'institution_phone';
    const INSTITUTION_EMAIL = 'institution_email';
    const INSTITUTION_WEBSITE = 'institution_website';
    const INSTITUTION_TAGLINE = 'institution_tagline';

    /**
     * Truthy check for a stored setting.
     *
     * Strict comparison against the canonical "on" values rather than a PHP
     * bool cast. The previous implementation was `(bool) static::get(...)`
     * which had two real bugs:
     *   - The string 'false' is non-empty, so `(bool) 'false'` is `true`,
     *     meaning a stored value of 'false' was reported as open.
     *   - The default for missing keys was 'false', and that also cast to
     *     `true`, so a missing key behaved as "open" instead of "closed".
     *
     * Both bugs masked the controller's form-submit path — saving an
     * unrelated setting wrote 'false' to every unchecked checkbox, but the
     * portal appeared to stay open because of the cast. Once callers
     * actually compare against 'true' (and friends), those writes close the
     * portal as the user expects.
     */
    public static function isOpen($key)
    {
        $value = static::get($key);
        return $value === 'true' || $value === true
            || $value === '1'   || $value === 1
            || $value === 'on';
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
            ['value' => $value ?? '']
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
     * Get house icon URL
     */
    public static function getHouseIcon()
    {
        return static::get(static::HOUSE_ICON, null);
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

    /**
     * Get payment portal URL
     */
    public static function getPaymentPortalUrl()
    {
        return static::get(static::PAYMENT_PORTAL_URL, null);
    }
}