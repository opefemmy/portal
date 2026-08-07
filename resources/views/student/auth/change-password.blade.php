@extends('layouts.app')

@section('title', 'Change Password')

@php
    // The auto-login flow (registrar-generated link) signs the student in
    // and forces a password change. We hide the "current password" field
    // in that case because the student has no usable password yet — they
    // were authenticated by the signed URL.
    $isForcedChange = auth()->user() && auth()->user()->must_change_password;
@endphp

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Your Password</h5>
            </div>
            <div class="card-body">
                @if($isForcedChange)
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Welcome! You signed in via a one-time link from the registrar.
                    Please set a new password to activate your student portal account.
                </div>
                @else
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    For security reasons, you must change your password before continuing.
                </div>
                @endif

                @if(session('info'))
                <div class="alert alert-warning">
                    {{ session('info') }}
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <form method="POST" action="{{ route('student.password.change') }}">
                    @csrf
                    @if(!$isForcedChange)
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current Password</label>
                        <input type="password" name="current_password" id="current_password"
                            class="form-control @error('current_password') is-invalid @enderror" required>
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    @endif

                    <div class="mb-3">
                        <label for="new_password" class="form-label">New Password</label>
                        <input type="password" name="new_password" id="new_password"
                            class="form-control @error('new_password') is-invalid @enderror" required>
                        @error('new_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">Minimum 6 characters. Do not use your matric number.</small>
                    </div>

                    <div class="mb-3">
                        <label for="new_password_confirmation" class="form-label">Confirm New Password</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation"
                            class="form-control" required>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-2"></i>{{ $isForcedChange ? 'Set Password & Continue' : 'Change Password' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection