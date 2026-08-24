<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    use EnforcesPermission;

    public function index(Request $request)
    {
        $this->requirePermission('admin.users.manage');
        // Filter to only show staff users - exclude students, applicants, parents, visitors
        $roleSlug    = $request->get('role', '');
        $onlyActive  = $request->boolean('active');

        $query = User::with(['role', 'roles', 'department'])
            // The user complained that "some users had been created
            // but hidden". The previous whereHas('role') clause
            // silently dropped any user whose `users.role_id`
            // looked up to nothing — orphan rows from a bad seed
            // or a deleted role. Use a LEFT JOIN style by
            // `with('role')` and a NULL-aware whereHas; the simple
            // fix is to drop the whereHas('role') requirement and
            // filter the role column in a way that ALSO keeps
            // orphan rows visible (we want to surface them, not
            // hide them).
            ->leftJoin('roles', 'roles.id', '=', 'users.role_id')
            ->select('users.*')
            ->where(function ($q) use ($roleSlug) {
                // Exclude student/applicant roles when their primary
                // role resolves; include rows where the role is
                // missing so admins can see & clean them up.
                $q->whereNull('roles.slug')
                  ->orWhereNotIn('roles.slug', ['student', 'applicant']);

                if ($roleSlug) {
                    $q->where('roles.slug', $roleSlug);
                }
            })
            ->when($onlyActive, fn ($q) => $q->where('users.is_active', true))
            ->latest('users.created_at')
            ->paginate(20);

        // Bind the paginator to $users so the view's compact() picks
        // it up. (Bug fixed: previously the local was named $query,
        // so compact('users', ...) raised "Undefined variable $users"
        // and /admin/users 500'd on PHP 8.2.)
        $users = $query;

        // Roles available to attach. We exclude `student` and
        // `applicant` to match the index's user scope — students are
        // managed on a separate page; same for applicants.
        $roles = Role::whereNotIn('slug', ['student', 'applicant'])
            ->orderBy('name')
            ->get();

        return view('admin.users.index', compact('users', 'roles', 'onlyActive'));
    }

    public function create()
    {
        $this->requirePermission('admin.users.manage');
        // Only show staff roles for creation
        $roles = Role::whereNotIn('slug', ['student', 'applicant'])->get();
        return view('admin.users.create', compact('roles'));
    }

    public function store(StoreUserRequest $request)
    {
        $this->requirePermission('admin.users.manage');
        $validated = $request->validated();

        // Create user with validated data
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role_id' => $validated['role_id'],
            'school_id' => $validated['school_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully');
    }

    public function show(User $user)
    {
        $this->requirePermission('admin.users.manage');
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->requirePermission('admin.users.manage');
        $roles = Role::all();
        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $this->requirePermission('admin.users.manage');
        $validated = $request->validated();

        // Update user with validated data
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'school_id' => $validated['school_id'] ?? null,
            'department_id' => $validated['department_id'] ?? null,
            'staff_id' => $validated['staff_id'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'gender' => $validated['gender'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully');
    }

    public function destroy(User $user)
    {
        $this->requirePermission('admin.users.manage');
        $user->delete();
        return back()->with('success', 'User deleted successfully');
    }

    public function activate(User $user)
    {
        $this->requirePermission('admin.users.manage');
        $user->update(['is_active' => true]);
        return back()->with('success', 'User activated');
    }

    public function deactivate(User $user)
    {
        $this->requirePermission('admin.users.manage');
        $user->update(['is_active' => false]);
        return back()->with('success', 'User deactivated');
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->requirePermission('admin.users.manage');
        $user->update(['password' => Hash::make('password')]);
        return back()->with('success', 'Password reset to default');
    }

    public function upload()
    {
        $this->requirePermission('admin.users.manage');
        $roles = Role::all();
        $schools = \App\Models\School::all();
        $departments = \App\Models\Department::all();
        $programmes = \App\Models\Programme::all();
        return view('admin.users.upload', compact('roles', 'schools', 'departments', 'programmes'));
    }

    public function processUpload(Request $request)
    {
        $this->requirePermission('admin.users.manage');
        $request->validate([
            'file' => 'required|mimes:csv,xlsx,xls|max:5120',
            'role_id' => 'required|exists:roles,id',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();

        $count = 0;
        $errors = [];
        $roleId = $request->role_id;
        $role = Role::find($roleId);
        $isStudent = $role && $role->slug === 'student';

        if ($extension === 'csv') {
            $data = array_map('str_getcsv', file($file));

            foreach ($data as $index => $row) {
                if ($index === 0 || empty($row[0])) continue; // Skip header

                try {
                    $email = trim($row[0] ?? '');
                    $name = trim($row[1] ?? '');
                    $schoolId = isset($row[2]) && !empty(trim($row[2])) ? (int)trim($row[2]) : null;
                    $departmentId = isset($row[3]) && !empty(trim($row[3])) ? (int)trim($row[3]) : null;
                    $programmeId = $isStudent && isset($row[4]) && !empty(trim($row[4])) ? (int)trim($row[4]) : null;
                    $level = $isStudent && isset($row[5]) && !empty(trim($row[5])) ? (int)trim($row[5]) : null;
                    $matricNumber = $isStudent && isset($row[6]) && !empty(trim($row[6])) ? trim($row[6]) : null;

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

                    $user = User::create([
                        'name' => $name,
                        'email' => $email,
                        'password' => Hash::make('password123'),
                        'role_id' => $roleId,
                        'school_id' => $schoolId,
                        'department_id' => $departmentId,
                        'is_active' => true,
                    ]);

                    // If student, create student profile
                    if ($isStudent) {
                        $session = \App\Models\Session::where('is_current', true)->first();
                        \App\Models\Student::create([
                            'user_id' => $user->id,
                            'matric_number' => $matricNumber ?? 'ND/' . date('Y') . '/' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                            'school_id' => $schoolId,
                            'department_id' => $departmentId,
                            'programme_id' => $programmeId,
                            'session_id' => $session?->id,
                            'level' => $level ?? 1,
                            'status' => 'active',
                        ]);
                    }
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

    public function uploadPassport(Request $request, User $user)
    {
        $this->requirePermission('admin.users.manage');
        $request->validate([
            'passport' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('passport')) {
            $file = $request->file('passport');
            $filename = $user->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('uploads/passports'), $filename);
            $user->update(['passport' => $filename]);
        }

        return back()->with('success', 'Passport uploaded successfully');
    }

    /**
     * Search users by name, email, staff_id, or matric_number
     */
    public function search(Request $request)
    {
        $this->requirePermission('admin.users.manage');
        $query = $request->get('search', '');
        $role = $request->get('role', '');

        $users = User::query()
            ->when($query, function($q) use ($query) {
                $q->where(function($q2) use ($query) {
                    $q2->where('name', 'like', "%{$query}%")
                       ->orWhere('email', 'like', "%{$query}%")
                       ->orWhere('staff_id', 'like', "%{$query}%")
                       ->orWhere('matric_number', 'like', "%{$query}%");
                });
            })
            ->when($role, function($q) use ($role) {
                $q->whereHas('role', function($q2) use ($role) {
                    $q2->where('slug', $role);
                });
            })
            ->where('is_active', true)
            ->limit(10)
            ->get(['id', 'name', 'email', 'staff_id', 'matric_number']);

        return response()->json($users);
    }
}