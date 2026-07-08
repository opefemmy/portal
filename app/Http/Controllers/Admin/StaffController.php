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
    public function index(Request $request)
    {
        $roleSlug = $request->role_slug;
        $search = $request->search;

        // Get staff roles (exclude student, applicant, super_admin)
        $staffRoles = Role::whereNotIn('slug', ['student', 'applicant', 'super_admin'])
            ->orderBy('name')
            ->get();

        // Build query - include all staff roles
        $query = User::whereHas('role', function ($q) {
            $q->whereNotIn('slug', ['student', 'applicant']);
        })->with('role');

        if ($roleSlug) {
            $query->whereHas('role', function ($q) use ($roleSlug) {
                $q->where('slug', $roleSlug);
            });
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $staff = $query->orderBy('name')->paginate(20);

        return view('admin.staff.index', compact('staff', 'staffRoles', 'roleSlug', 'search'));
    }

    public function create()
    {
        $roles = Role::whereNotIn('slug', ['student', 'applicant', 'super_admin'])
            ->orderBy('name')
            ->get();
        return view('admin.staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);
        return redirect()->route('admin.staff.index')->with('success', 'Staff member created successfully');
    }

    public function show(User $staff)
    {
        return view('admin.staff.show', compact('staff'));
    }

    public function edit(User $staff)
    {
        $roles = Role::whereNotIn('slug', ['student', 'applicant', 'super_admin'])
            ->orderBy('name')
            ->get();
        return view('admin.staff.edit', compact('staff', 'roles'));
    }

    public function update(Request $request, User $staff)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($staff->id)],
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->password);
        }

        $staff->update($validated);
        return redirect()->route('admin.staff.index')->with('success', 'Staff member updated successfully');
    }

    public function destroy(User $staff)
    {
        if ($staff->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account');
        }

        $staff->delete();
        return back()->with('success', 'Staff member deleted successfully');
    }

    public function resetPassword(Request $request, User $staff)
    {
        $newPassword = $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        $staff->update(['password' => Hash::make($newPassword['new_password'])]);
        return back()->with('success', 'Password reset successfully for ' . $staff->name);
    }
}