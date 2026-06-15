@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="page-header">
    <h4>My Profile</h4>
</div>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="avatar avatar-lg mx-auto mb-3">
                    <i class="fas fa-user-circle fa-5x text-primary"></i>
                </div>
                <h5>{{ auth()->user()->name }}</h5>
                <p class="text-muted">{{ auth()->user()->role->name ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5>Personal Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" value="{{ auth()->user()->name }}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="{{ auth()->user()->email }}" disabled>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ auth()->user()->phone }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control" value="{{ auth()->user()->state }}">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3">{{ auth()->user()->address }}</textarea>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5>Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-warning">Change Password</button>
                </form>
            </div>
        </div>

        <!-- Secret Question for Password Reset -->
        <div class="card mt-4">
            <div class="card-header">
                <h5>Security Question (for Password Reset)</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update-secret') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Secret Question</label>
                        <input type="text" name="secret_question" class="form-control" value="{{ auth()->user()->secret_question ?? '' }}" placeholder="e.g., What is your mother's maiden name?" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Your Answer</label>
                        <input type="text" name="secret_answer" class="form-control" value="{{ auth()->user()->secret_answer ?? '' }}" placeholder="Your secret answer" required>
                    </div>
                    <button type="submit" class="btn btn-info">Save Security Question</button>
                </form>
            </div>
        </div>

        <!-- Student Info -->
        @if(isset($student) && $student)
        <div class="card mt-4">
            <div class="card-header">
                <h5>Academic Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr><th>Matric Number:</th><td>{{ $student->matric_number }}</td></tr>
                    <tr><th>Department:</th><td>{{ $student->department->name ?? 'N/A' }}</td></tr>
                    <tr><th>Programme:</th><td>{{ $student->programme->name ?? 'N/A' }}</td></tr>
                    <tr><th>Level:</th><td>{{ $student->level_display }}</td></tr>
                    <tr><th>Session:</th><td>{{ $student->session->name ?? 'N/A' }}</td></tr>
                </table>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection