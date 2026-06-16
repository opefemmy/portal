<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role', 'department')->latest()->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'required|exists:roles,id',
        ]);

        User::create(array_merge($validated, [
            'password' => Hash::make($validated['password']),
        ]));

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->update($validated);
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User deleted successfully');
    }

    public function activate(User $user)
    {
        $user->update(['is_active' => true]);
        return back()->with('success', 'User activated');
    }

    public function deactivate(User $user)
    {
        $user->update(['is_active' => false]);
        return back()->with('success', 'User deactivated');
    }

    public function resetPassword(Request $request, User $user)
    {
        $user->update(['password' => Hash::make('password')]);
        return back()->with('success', 'Password reset to default');
    }

    public function upload()
    {
        $roles = Role::all();
        $schools = \App\Models\School::all();
        $departments = \App\Models\Department::all();
        return view('admin.users.upload', compact('roles', 'schools', 'departments'));
    }

    public function processUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:5120',
            'role_id' => 'required|exists:roles,id',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $count = 0;
        $errors = [];

        if ($extension === 'csv') {
            $data = array_map('str_getcsv', file($file));

            foreach ($data as $index => $row) {
                if ($index === 0 || empty($row[0])) continue; // Skip header

                try {
                    $email = trim($row[0] ?? '');
                    $name = trim($row[1] ?? '');
                    $roleId = $request->role_id;
                    $schoolId = isset($row[2]) && !empty(trim($row[2])) ? trim($row[2]) : null;
                    $departmentId = isset($row[3]) && !empty(trim($row[3])) ? trim($row[3]) : null;

                    if (empty($email) || empty($name)) {
                        $errors[] = "Row $index: Email or name is empty";
                        continue;
                    }

                    // Check if user exists
                    $exists = User::where('email', $email)->first();
                    if ($exists) {
                        $errors[] = "Row $index: User with email $email already exists";
                        continue;
                    }

                    User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password123'),
                        'role_id' => $roleId,
                        'school_id' => $schoolId,
                        'department_id' => $departmentId,
                        'is_active' => true,
                    ]);
                    $count++;
                } catch (\Exception $e) {
                    $errors[] = "Row $index: " . $e->getMessage();
                }
            }
        }

        if ($count > 0) {
            return redirect()->route('admin.users.index')->with('success', "$count users uploaded successfully");
        }

        return back()->with('error', 'No users uploaded. ' . implode(', ', $errors));
    }
}