@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="page-header">
    <h4>Edit User</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.users.update', $user) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror"
                    id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror"
                    id="email" name="email" value="{{ old('email', $user->email) }}" required>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="role_id" class="form-label">Role</label>
                <select class="form-select @error('role_id') is-invalid @enderror"
                    id="role_id" name="role_id" required>
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <!-- HOD/Dean specific fields - show only when relevant role is selected -->
            <div id="school-department-fields" style="display: none;">
                <div class="mb-3">
                    <label for="school_id" class="form-label">School</label>
                    <select class="form-select" id="school_id" name="school_id">
                        <option value="">Select School</option>
                        @foreach(\App\Models\School::all() as $school)
                            <option value="{{ $school->id }}" {{ old('school_id', $user->school_id) == $school->id ? 'selected' : '' }}>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="department_id" class="form-label">Department</label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">Select Department</option>
                        @if($user->department)
                            <option value="{{ $user->department->id }}" selected>{{ $user->department->name }}</option>
                        @endif
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label for="staff_id" class="form-label">Staff ID</label>
                <input type="text" class="form-control"
                    id="staff_id" name="staff_id" value="{{ old('staff_id', $user->staff_id) }}">
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input"
                        id="is_active" name="is_active" value="1"
                        {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">
                        Active
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <button type="submit" class="btn btn-primary">Update User</button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role_id');
    const schoolDeptFields = document.getElementById('school-department-fields');

    function toggleFields() {
        const selectedOption = roleSelect.options[roleSelect.selectedIndex];
        const roleSlug = selectedOption?.text?.toLowerCase() || '';

        // Show school/department fields for HOD and Dean roles
        if (roleSlug.includes('hod') || roleSlug.includes('dean')) {
            schoolDeptFields.style.display = 'block';
        } else {
            schoolDeptFields.style.display = 'none';
        }
    }

    roleSelect.addEventListener('change', toggleFields);
    toggleFields(); // Initial check

    // Cascade dropdown: departments based on school
    const schoolSelect = document.getElementById('school_id');
    const deptSelect = document.getElementById('department_id');

    schoolSelect.addEventListener('change', function() {
        const schoolId = this.value;
        deptSelect.innerHTML = '<option value="">Select Department</option>';

        if (schoolId) {
            fetch('/api/departments/' + schoolId)
                .then(response => response.json())
                .then(data => {
                    data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.id;
                        option.textContent = dept.name;
                        deptSelect.appendChild(option);
                    });
                });
        }
    });
});
</script>
@endpush
@endsection
