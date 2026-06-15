<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::whereHas('role', function ($query) {
            $query->whereIn('slug', ['lecturer', 'hod', 'dean', 'registrar', 'bursar', 'admin', 'staff']);
        })->with('role')->latest()->get();

        return view('admin.staff.index', compact('staff'));
    }

    public function create()
    {
        $roles = Role::whereIn('slug', ['lecturer', 'hod', 'dean', 'registrar', 'bursar', 'admin', 'staff'])->get();
        return view('admin.staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);
        return redirect()->route('admin.staff.index')->with('success', 'Staff member created successfully');
    }

    public function edit(User $staff)
    {
        $roles = Role::whereIn('slug', ['lecturer', 'hod', 'dean', 'registrar', 'bursar', 'admin', 'staff'])->get();
        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($staff->id)],
            'role_id' => 'required|exists:roles,id',
            'is_active' => 'boolean',
        ]);

        if ($request->filled('password')) {
            $request->validate([
                'password' => 'string|min:8|confirmed',
            ]);
            $validated['password'] = Hash::make($request->password);
        }

        $staff->update($validated);
        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully');
    }

    public function destroy(User $staff)
    {
        $staff->delete();
        return back()->with('success', 'Staff member deleted successfully');
    }

    public function resetPassword(Request $request, User $staff)
    {
        $newPassword = $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $staff->update(['password' => Hash::make($newPassword['new_password'])]);
        return back()->with('success', 'Password reset successfully for ' . $staff->name);
    }
}