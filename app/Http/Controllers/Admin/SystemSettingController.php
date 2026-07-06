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
        ];

        foreach ($settingsKeys as $key) {
            $value = $request->input($key, 'false');
            SystemSetting::set($key, $value);
        }

        // Also handle penalty amounts if provided as numeric
        foreach (['admission_form_penalty', 'course_registration_penalty', 'payment_penalty'] as $penaltyKey) {
            if ($request->has($penaltyKey . '_amount')) {
                $amount = $request->input($penaltyKey . '_amount', 0);
                SystemSetting::set($penaltyKey . '_amount', $amount);
            }
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
}