<?php

use App\Models\Setting;

if (!function_exists('setting')) {
    function setting($key, $default = null)
    {
        return Setting::get($key, $default);
    }
}

if (!function_exists('generate_matric_number')) {
    function generate_matric_number($session, $programme, $department)
    {
        $prefix = $department->code;
        $year = substr($session->name, 0, 4);
        $random = strtoupper(uniqid());
        return "{$prefix}/{$year}/{$random}";
    }
}