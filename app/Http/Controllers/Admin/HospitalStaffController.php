<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\EnforcesPermission;
use App\Models\User;
use App\Models\Role;
use App\Models\Hospital\HospitalStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class HospitalStaffController extends Controller
{
    use EnforcesPermission;

    protected $staffTypes = [
        'doctor' => 'Doctor',
        'nurse' => 'Nurse',
        'pharmacist' => 'Pharmacist',
        'lab_scientist' => 'Lab Scientist',
        'receptionist' => 'Receptionist',
        'cashier' => 'Cashier',
        'admin' => 'Hospital Admin',
        'matron' => 'Matron',
        'ward_manager' => 'Ward Manager',
    ];

    public function index(Request $request)
    {
        $this->requirePermission('admin.hospital-staff.manage');

        $type = $request->type;
        $search = $request->search;
        $staffTypes = $this->staffTypes;

        $query = HospitalStaff::with('user')
            ->where('is_active', true);

        if ($type && array_key_exists($type, $this->staffTypes)) {
            $query->where('staff_type', $type);
        }

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('staff_number', 'like', "%{$search}%")
                  ->orWhereHas('user', function($uq) use ($search) {
                      $uq->where('email', 'like', "%{$search}%");
                  });
            });
        }

        $staff = $query->orderBy('first_name')->paginate(20);

        return view('admin.hospital.staff.index', compact('staff', 'staffTypes', 'type', 'search'));
    }

    public function create()
    {
        $this->requirePermission('admin.hospital-staff.manage');

        $roles = Role::where(function($q) {
            $q->where('name', 'like', '%Hospital%')
              ->orWhere('name', 'like', '%Medical%')
              ->orWhere('name', 'like', '%Nurse%')
              ->orWhere('name', 'like', '%Doctor%')
              ->orWhere('name', 'like', '%Pharmacist%')
              ->orWhere('name', 'like', '%Lab%')
              ->orWhere('name', 'like', '%Receptionist%')
              ->orWhere('name', 'like', '%Cashier%')
              ->orWhere('name', 'like', '%Matron%')
              ->orWhere('name', 'like', '%Ward%');
        })->orderBy('name')->get();
        $staffTypes = $this->staffTypes;

        return view('admin.hospital.staff.create', compact('roles', 'staffTypes'));
    }



    public function store(Request $request)
    {
        $this->requirePermission('admin.hospital-staff.manage');

        $validatedUser = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        $validatedStaff = $request->validate([
            'staff_number' => 'required|string|unique:hospital_staff,staff_number',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'staff_type' => ['required', Rule::in(array_keys($this->staffTypes))],
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:100',
            'license_expiry' => 'nullable|date',
            'specialization' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'name' => $validatedUser['name'],
                'email' => $validatedUser['email'],
                'password' => Hash::make($validatedUser['password']),
                'role_id' => $validatedUser['role_id'],
            ]);

            HospitalStaff::create([
                'user_id' => $user->id,
                'staff_number' => $validatedStaff['staff_number'],
                'first_name' => $validatedStaff['first_name'],
                'last_name' => $validatedStaff['last_name'],
                'staff_type' => $validatedStaff['staff_type'],
                'phone' => $validatedStaff['phone'],
                'license_number' => $validatedStaff['license_number'],
                'license_expiry' => $validatedStaff['license_expiry'],
                'specialization' => $validatedStaff['specialization'],
                'is_active' => true,
                'is_available' => true,
            ]);

            DB::commit();
            return redirect()->route('admin.hospital-staff.index')->with('success', 'Hospital staff member created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to create staff member: ' . $e->getMessage())->withInput();
        }
    }

    public function edit(HospitalStaff $staff)
    {
        $this->requirePermission('admin.hospital-staff.manage');

        $roles = Role::where(function($q) {
            $q->where('name', 'like', '%Hospital%')
              ->orWhere('name', 'like', '%Medical%')
              ->orWhere('name', 'like', '%Nurse%')
              ->orWhere('name', 'like', '%Doctor%')
              ->orWhere('name', 'like', '%Pharmacist%')
              ->orWhere('name', 'like', '%Lab%')
              ->orWhere('name', 'like', '%Receptionist%')
              ->orWhere('name', 'like', '%Cashier%')
              ->orWhere('name', 'like', '%Matron%')
              ->orWhere('name', 'like', '%Ward%');
        })->orderBy('name')->get();
        $staffTypes = $this->staffTypes;

        return view('admin.hospital.staff.edit', compact('staff', 'roles', 'staffTypes'));
    }

    public function update(Request $request, HospitalStaff $staff)
    {
        $this->requirePermission('admin.hospital-staff.manage');

        $user = $staff->user;
        if (!$user) {
            return back()->with('error', 'Associated user not found');
        }

        $validatedUser = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role_id' => 'required|exists:roles,id',
        ]);

        $validatedStaff = $request->validate([
            'staff_number' => ['required', 'string', Rule::unique('hospital_staff')->ignore($staff->id)],
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'staff_type' => ['required', Rule::in(array_keys($this->staffTypes))],
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string|max:100',
            'license_expiry' => 'nullable|date',
            'specialization' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_available' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $user->update([
                'name' => $validatedUser['name'],
                'email' => $validatedUser['email'],
                'role_id' => $validatedUser['role_id'],
            ]);

            if ($request->filled('password')) {
                $user->update(['password' => Hash::make($request->password)]);
            }

            $staff->update($validatedStaff);

            DB::commit();
            return redirect()->route('admin.hospital-staff.index')->with('success', 'Hospital staff member updated successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to update staff member: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy(HospitalStaff $staff)
    {
        $this->requirePermission('admin.hospital-staff.manage');

        $user = $staff->user;

        try {
            DB::beginTransaction();

            if ($user) {
                $user->delete();
            }

            $staff->delete();

            DB::commit();
            return back()->with('success', 'Hospital staff member deleted successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to delete staff member: ' . $e->getMessage());
        }
    }
}
