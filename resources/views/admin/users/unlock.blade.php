@extends('layouts.app')

@section('title', 'Unlock User Account')

@section('content')
<div class="page-header">
    <h4>Unlock User Account</h4>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-unlock me-2"></i>Generate Unlock Code</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Generate a unique unlock code for a user. The user will need to enter this code along with a new password to unlock their account.
                </p>

                @if(session('success') && session('unlock_code'))
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle me-2"></i>Unlock Code Generated!</h5>
                    <p class="mb-1"><strong>User:</strong> {{ session('user_email') }}</p>
                    <p class="mb-0"><strong>Unlock Code:</strong> <code class="fs-5">{{ session('unlock_code') }}</code></p>
                    <hr>
                    <p class="mb-0 text-muted">Share this code with the user. It expires in 24 hours.</p>
                </div>
                @endif

                <form method="POST" action="{{ route('admin.users.unlock.generate') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="email" class="form-label">User Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <small class="text-muted">Enter the email of the user you want to unlock</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key me-2"></i>Generate Unlock Code
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Unlock (Direct Reset)</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">
                    Directly reset a user's password without generating an unlock code. The user will be required to change their password on next login.
                </p>

                <form method="POST" action="{{ route('admin.users.unlock.quick') }}" id="quickUnlockForm">
                    @csrf
                    <div class="mb-3">
                        <label for="user_id" class="form-label">Select User</label>
                        <select class="form-select select2" id="user_id" name="user_id" required>
                            <option value="">Select a user...</option>
                            @foreach(\App\Models\User::where('is_active', true)->orderBy('name')->get() as $user)
                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-bolt me-2"></i>Quick Unlock
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Recent Unlocks</h5>
            </div>
            <div class="card-body">
                @php
                $recentUnlocks = \App\Models\User::whereNotNull('password_changed_at')
                    ->orderBy('password_changed_at', 'desc')
                    ->limit(5)
                    ->get();
                @endphp

                @if($recentUnlocks->count() > 0)
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Changed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentUnlocks as $user)
                        <tr>
                            <td>{{ $user->name }}<br><small class="text-muted">{{ $user->email }}</small></td>
                            <td>{{ $user->password_changed_at->diffForHumans() }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted mb-0">No recent password changes</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
