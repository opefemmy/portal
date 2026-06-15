<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $data = [
            'scrolling_message' => Setting::get('scrolling_message', ''),
            'login_notification' => Setting::get('login_notification', ''),
            'post_login_message' => Setting::get('post_login_message', ''),
            'show_post_login_popup' => Setting::get('show_post_login_popup', false),
        ];
        return view('admin.notifications.index', $data);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'scrolling_message' => 'nullable|string|max:500',
            'login_notification' => 'nullable|string',
            'post_login_message' => 'nullable|string',
            'show_post_login_popup' => 'boolean',
        ]);

        Setting::set('scrolling_message', $validated['scrolling_message'] ?? '');
        Setting::set('login_notification', $validated['login_notification'] ?? '');
        Setting::set('post_login_message', $validated['post_login_message'] ?? '');
        Setting::set('show_post_login_popup', $validated['show_post_login_popup'] ?? false);

        return redirect()->route('admin.notifications.index')->with('success', 'Notification settings updated');
    }
}