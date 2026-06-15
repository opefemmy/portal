<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user();
        $student = $user->student ?? null;
        return view('profile.show', compact('student'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'state' => 'nullable|string|max:100',
            'lga' => 'nullable|string|max:100',
        ]);

        $user->update($validated);
        return back()->with('success', 'Profile updated successfully');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->with('error', 'Current password is incorrect');
        }

        $user->update(['password' => Hash::make($request->password)]);
        return back()->with('success', 'Password updated successfully');
    }

    public function updateSecretQuestion(Request $request)
    {
        $request->validate([
            'secret_question' => 'required|string|max:255',
            'secret_answer' => 'required|string|max:255',
        ]);

        $user = auth()->user();
        $user->update([
            'secret_question' => $request->secret_question,
            'secret_answer' => $request->secret_answer,
        ]);

        return back()->with('success', 'Secret question updated successfully');
    }
}