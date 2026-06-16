@extends('layouts.app')

@section('title', 'Library Access')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header text-center">
                <h4><i class="fas fa-book"></i> Library Access Required</h4>
            </div>
            <div class="card-body">
                <p class="text-center">Please enter the library access code to continue.</p>

                <form method="POST" action="{{ route('library.verify.post') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="code" class="form-label">Access Code</label>
                        <input type="password" class="form-control" id="code" name="code" required>
                    </div>

                    @error('error')
                        <div class="alert alert-danger">{{ $message }}</div>
                    @enderror

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Access Library
                    </button>
                </form>

                <p class="text-center mt-3">
                    <a href="{{ route('admin.dashboard') }}">Back to Dashboard</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection