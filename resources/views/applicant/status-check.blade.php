@extends('layouts.app')

@section('title', 'Check Application Status')

@section('content')
<div class="page-header">
    <h4>Check Application Status</h4>
</div>
<div class="card">
    <div class="card-body">
        <p class="text-muted">Enter your application number or email to check the status of your application.</p>
        <form method="POST" action="{{ route('applicant.status.check') }}">
            @csrf
            <div class="form-group">
                <label for="application_number">Application Number / Email</label>
                <input type="text" name="application_number" id="application_number"
                       class="form-control @error('application_number') is-invalid @enderror"
                       value="{{ old('application_number') }}" required>
                @error('application_number')
                    <span class="invalid-feedback">{{ $message }}</span>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">Check Status</button>
        </form>

        @if(isset($status))
            <hr>
            <h5 class="mt-4">Application Status</h5>
            @if($status)
                <div class="alert alert-info">
                    <p class="mb-1"><strong>Status:</strong> {{ $status->status ?? 'Pending' }}</p>
                    @if(isset($status->application_number))
                        <p class="mb-1"><strong>Application No:</strong> {{ $status->application_number }}</p>
                    @endif
                    @if(isset($status->full_name))
                        <p class="mb-1"><strong>Name:</strong> {{ $status->full_name }}</p>
                    @endif
                    @if(isset($status->programme))
                        <p class="mb-1"><strong>Programme:</strong> {{ $status->programme }}</p>
                    @endif
                    @if(isset($status->created_at))
                        <p class="mb-0"><strong>Submitted:</strong> {{ $status->created_at->format('d M Y') }}</p>
                    @endif
                </div>
            @else
                <div class="alert alert-warning">No application found with the provided details.</div>
            @endif
        @endif
    </div>
</div>
@endsection
