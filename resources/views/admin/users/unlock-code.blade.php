@extends('layouts.app')

@section('title', 'Unlock Account')

@section('content')
<div class="page-header">
    <h4>Unlock Your Account</h4>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-unlock me-2"></i>Set New Password</h5>
            </div>
            <div class="card-body">
                @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <div class="alert alert-info">
                    <p class="mb-0">Your account has been unlocked. Please set a new password to continue.</p>
                </div>

                <form method="POST" action="{{ route('admin.users.unlock.process') }}">
                    @csrf

                    <input type="hidden" name="email" value="{{ $email }}">
                    <input type="hidden" name="unlock_code" value="{{ $code }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="text" class="form-control" value="{{ $email }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="unlock_code" class="form-label">Unlock Code</label>
                        <input type="text" class="form-control" value="{{ $code }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                        <small class="text-muted">Minimum 8 characters</small>
                    </div>

                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control" id="new_password_confirmation" name="new_password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-unlock me-2"></i>Unlock Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
