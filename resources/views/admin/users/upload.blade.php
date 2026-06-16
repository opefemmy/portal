@extends('layouts.app')

@section('title', 'Upload Users')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Upload Users</h4>
    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Back to Users</a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5>Upload Instructions</h5>
    </div>
    <div class="card-body">
        <p>Upload a CSV file with the following columns:</p>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Required</th>
                    <th>For Students</th>
                    <th>For Staff</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>1</td>
                    <td><strong>Email</strong> (required)</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td><strong>Name</strong> (required)</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>School ID</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>4</td>
                    <td>Department ID</td>
                    <td>✓</td>
                    <td>✓</td>
                </tr>
                <tr>
                    <td>5</td>
                    <td>Programme ID</td>
                    <td>✓ (for students)</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>6</td>
                    <td>Level (1-6)</td>
                    <td>✓ (for students)</td>
                    <td>-</td>
                </tr>
                <tr>
                    <td>7</td>
                    <td>Matric Number</td>
                    <td>✓ (optional)</td>
                    <td>-</td>
                </tr>
            </tbody>
        </table>
        <p class="text-muted mt-2">Default password for all uploaded users: <strong>password123</strong></p>
    </div>
</div>

<form action="{{ route('admin.users.upload.process') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="card mb-4">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Select Role *</label>
                <select name="role_id" class="form-select" required id="roleSelect">
                    <option value="">Select Role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Upload CSV File *</label>
                <input type="file" name="file" class="form-control" accept=".csv,.xlsx,.xls" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Available Schools (for School ID)</label>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                        <tr>
                            <td>{{ $school->id }}</td>
                            <td>{{ $school->name }}</td>
                            <td>{{ $school->code }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3">No schools available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mb-3">
                <label class="form-label">Available Departments (for Department ID)</label>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>School</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departments as $dept)
                        <tr>
                            <td>{{ $dept->id }}</td>
                            <td>{{ $dept->name }}</td>
                            <td>{{ $dept->school->name ?? 'N/A' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3">No departments available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mb-3" id="programmeSection" style="display:none;">
                <label class="form-label">Available Programmes (for Programme ID)</label>
                <table class="table table-sm table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($programmes as $prog)
                        <tr>
                            <td>{{ $prog->id }}</td>
                            <td>{{ $prog->name }}</td>
                            <td>{{ $prog->code }}</td>
                            <td>{{ $prog->type }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="4">No programmes available</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-upload me-2"></i>Upload Users
    </button>
</form>

@push('scripts')
<script>
document.getElementById('roleSelect').addEventListener('change', function() {
    // Show programme section for student role (slug: student)
    // You may need to adjust based on actual role slug
    var studentRoleId = {{ $roles->firstWhere('slug', 'student')?->id ?? 'null' }};
    var section = document.getElementById('programmeSection');
    if (this.value == studentRoleId) {
        section.style.display = 'block';
    } else {
        section.style.display = 'none';
    }
});
</script>
@endpush
@endsection