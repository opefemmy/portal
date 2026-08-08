<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function index()
    {
        $settings = SystemSetting::all()->keyBy('key');
        $gateways = PaymentGateway::all();

        return view('admin.settings.index', compact('settings', 'gateways'));
    }

    public function updateSettings(Request $request)
    {
        $settingsKeys = [
            'admission_form_open',
            'admission_form_penalty',
            'course_registration_open',
            'course_registration_penalty',
            'payment_open',
            'payment_penalty',
            'result_upload_open',
            // Late payment settings for specific fees
            'late_school_fee_enabled',
            'late_course_reg_enabled',
            'late_other_fee_enabled',
        ];

        foreach ($settingsKeys as $key) {
            // Only persist settings the form actually submitted. Without
            // this guard, saving an unrelated section (e.g. toggling
            // admission-form-open) would clobber every other unchecked
            // checkbox back to 'false' — most painfully `payment_open`,
            // which silently closes the student payment portal. The
            // late-fee and library-fee loops below already use this
            // pattern; this brings the top-level loop in line with them.
            if ($request->has($key)) {
                $value = $request->input($key, 'false');
                SystemSetting::set($key, $value);
            }
        }

        // Also handle penalty amounts if provided as numeric
        foreach (['admission_form_penalty', 'course_registration_penalty', 'payment_penalty'] as $penaltyKey) {
            if ($request->has($penaltyKey . '_amount')) {
                $amount = $request->input($penaltyKey . '_amount', 0);
                SystemSetting::set($penaltyKey . '_amount', $amount);
            }
        }

        // Handle late fee amounts
        $lateFeeKeys = [
            'late_school_fee_amount',
            'late_course_reg_amount',
            'late_other_fee_amount',
        ];

        foreach ($lateFeeKeys as $key) {
            if ($request->has($key)) {
                $amount = $request->input($key, 0);
                SystemSetting::set($key, $amount);
            }
        }

        // Library fee settings
        $libraryKeys = [
            'library_fee_required',
            'library_fee_amount',
            'library_late_fee_per_day',
            'library_max_borrow_days',
        ];

        foreach ($libraryKeys as $key) {
            $value = $key === 'library_fee_amount' || $key === 'library_late_fee_per_day' || $key === 'library_max_borrow_days'
                ? $request->input($key, 0)
                : $request->input($key, 'false');
            SystemSetting::set($key, $value);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully!');
    }

    public function updateGateways(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'test_public_key' => 'nullable|string',
            'test_secret_key' => 'nullable|string',
            'live_public_key' => 'nullable|string',
            'live_secret_key' => 'nullable|string',
        ]);

        $gateway = PaymentGateway::updateOrCreate(
            ['provider' => $request->provider],
            [
                'test_public_key' => $request->test_public_key,
                'test_secret_key' => $request->test_secret_key,
                'live_public_key' => $request->live_public_key,
                'live_secret_key' => $request->live_secret_key,
                'is_test_mode' => $request->boolean('is_test_mode', true),
                'is_active' => $request->boolean('is_active', true),
            ]
        );

        return redirect()->route('admin.settings.index')
            ->with('success', 'Payment gateway updated successfully!');
    }

    public function setActiveGateway(PaymentGateway $gateway)
    {
        // Deactivate all other gateways
        PaymentGateway::where('id', '!=', $gateway->id)->update(['is_active' => false]);

        // Activate the selected one
        $gateway->update(['is_active' => true]);

        return redirect()->route('admin.settings.index')
            ->with('success', 'Active gateway updated!');
    }

    public function toggleSetting(Request $request)
    {
        $key = $request->input('key');
        $value = $request->input('value', 'false');

        SystemSetting::set($key, $value);

        return response()->json([
            'success' => true,
            'message' => 'Setting updated',
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * Update branding settings
     */
    public function updateBranding(Request $request)
    {
        // Validate text fields
        $request->validate([
            'institution_name' => 'nullable|string|max:255',
            'institution_short_name' => 'nullable|string|max:50',
            'institution_address' => 'nullable|string|max:500',
            'institution_phone' => 'nullable|string|max:30',
            'institution_email' => 'nullable|email|max:100',
            'institution_website' => 'nullable|url|max:100',
            'institution_tagline' => 'nullable|string|max:255',
        ]);

        $brandingKeys = [
            'institution_name',
            'institution_short_name',
            'institution_address',
            'institution_phone',
            'institution_email',
            'institution_website',
            'institution_tagline',
        ];

        foreach ($brandingKeys as $key) {
            if ($request->has($key)) {
                SystemSetting::set($key, $request->input($key));
            }
        }

        // Validate and handle logo upload
        if ($request->hasFile('institution_logo')) {
            $request->validate([
                'institution_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            ]);

            $logo = $request->file('institution_logo');

            // Verify the image is not corrupted by trying to get image dimensions
            if (!@getimagesize($logo->getRealPath())) {
                return back()->with('error', 'The logo file appears to be corrupted or is not a valid image.');
            }

            $logoName = 'logo_' . time() . '.' . $logo->getClientOriginalExtension();
            $logo->storeAs('public/branding', $logoName);
            SystemSetting::set('institution_logo', 'branding/' . $logoName);
        }

        // Validate and handle icon/favicon upload
        if ($request->hasFile('institution_icon')) {
            $request->validate([
                'institution_icon' => 'required|image|mimes:jpeg,png,jpg,gif,ico,svg,webp|max:1024',
            ]);

            $icon = $request->file('institution_icon');

            // Verify the image is not corrupted
            if (!@getimagesize($icon->getRealPath())) {
                return back()->with('error', 'The favicon file appears to be corrupted or is not a valid image.');
            }

            $iconName = 'icon_' . time() . '.' . $icon->getClientOriginalExtension();
            $icon->storeAs('public/branding', $iconName);
            SystemSetting::set('institution_icon', 'branding/' . $iconName);
        }

        // Validate and handle house icon upload (for patient portal home)
        if ($request->hasFile('house_icon')) {
            $request->validate([
                'house_icon' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:1024',
            ]);

            $houseIcon = $request->file('house_icon');

            // Verify the image is not corrupted
            if (!@getimagesize($houseIcon->getRealPath())) {
                return back()->with('error', 'The house icon file appears to be corrupted or is not a valid image.');
            }

            $houseIconName = 'house_icon_' . time() . '.' . $houseIcon->getClientOriginalExtension();
            $houseIcon->storeAs('public/branding', $houseIconName);
            SystemSetting::set('house_icon', 'branding/' . $houseIconName);
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Branding settings updated successfully!');
    }

    /**
     * Download institution logo
     */
    public function downloadLogo()
    {
        $logo = SystemSetting::get('institution_logo');

        if (!$logo) {
            return back()->with('error', 'No logo uploaded yet.');
        }

        $path = storage_path('app/public/' . $logo);

        if (!file_exists($path)) {
            return back()->with('error', 'Logo file not found.');
        }

        return response()->download($path);
    }

    /**
     * Download institution icon/favicon
     */
    public function downloadIcon()
    {
        $icon = SystemSetting::get('institution_icon');

        if (!$icon) {
            return back()->with('error', 'No icon uploaded yet.');
        }

        $path = storage_path('app/public/' . $icon);

        if (!file_exists($path)) {
            return back()->with('error', 'Icon file not found.');
        }

        return response()->download($path);
    }

    /**
     * Download house icon
     */
    public function downloadHouseIcon()
    {
        $houseIcon = SystemSetting::get('house_icon');

        if (!$houseIcon) {
            return back()->with('error', 'No house icon uploaded yet.');
        }

        $path = storage_path('app/public/' . $houseIcon);

        if (!file_exists($path)) {
            return back()->with('error', 'House icon file not found.');
        }

        return response()->download($path);
    }

    /**
     * Delete institution logo
     */
    public function deleteLogo()
    {
        $logo = SystemSetting::get('institution_logo');

        if ($logo) {
            $path = storage_path('app/public/' . $logo);
            if (file_exists($path)) {
                unlink($path);
            }
            SystemSetting::set('institution_logo', null);
        }

        return back()->with('success', 'Logo deleted successfully.');
    }

    /**
     * Delete institution icon
     */
    public function deleteIcon()
    {
        $icon = SystemSetting::get('institution_icon');

        if ($icon) {
            $path = storage_path('app/public/' . $icon);
            if (file_exists($path)) {
                unlink($path);
            }
            SystemSetting::set('institution_icon', null);
        }

        return back()->with('success', 'Icon deleted successfully.');
    }

    /**
     * Delete house icon
     */
    public function deleteHouseIcon()
    {
        $houseIcon = SystemSetting::get('house_icon');

        if ($houseIcon) {
            $path = storage_path('app/public/' . $houseIcon);
            if (file_exists($path)) {
                unlink($path);
            }
            SystemSetting::set('house_icon', null);
        }

        return back()->with('success', 'House icon deleted successfully.');
    }
}