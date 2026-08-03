@extends('layouts.app')

@section('title', 'Lab Request #' . $labRequest->id)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="fas fa-vial me-2"></i>Lab Request #{{ $labRequest->id }}</h3>
            <small class="text-muted">{{ $labRequest->patient->full_name ?? '—' }} · {{ $labRequest->patient->patient_number ?? '' }}</small>
        </div>
        <a href="{{ route('hospital.lab.index') }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white"><i class="fas fa-info-circle me-1"></i> Request</div>
                <div class="card-body">
                    <p class="mb-2"><strong>Test:</strong> {{ $labRequest->test_type }}</p>
                    <p class="mb-2"><strong>Status:</strong>
                        <span class="badge bg-{{ $labRequest->status === 'completed' ? 'success' : ($labRequest->status === 'pending' ? 'warning' : 'primary') }}">
                            {{ ucfirst($labRequest->status) }}
                        </span>
                    </p>
                    <p class="mb-2"><strong>Doctor:</strong> {{ $labRequest->doctor?->full_name ?? '—' }}</p>
                    <p class="mb-2"><strong>Requested:</strong> {{ optional($labRequest->requested_at)->format('d M Y H:i') }}</p>
                    @if($labRequest->completed_at)
                        <p class="mb-2"><strong>Completed:</strong> {{ $labRequest->completed_at->format('d M Y H:i') }}</p>
                    @endif
                    @if($labRequest->clinical_notes)
                        <hr>
                        <p class="mb-1"><strong>Clinical notes:</strong></p>
                        <p class="small text-muted">{{ $labRequest->clinical_notes }}</p>
                    @endif
                </div>
            </div>

            @if(in_array($labRequest->status, ['pending','sample_collected']))
                <div class="card mt-3">
                    <div class="card-header bg-warning"><i class="fas fa-tasks me-1"></i> Actions</div>
                    <div class="card-body">
                        @if($labRequest->status === 'pending')
                            <form method="POST" action="{{ route('hospital.lab.collect', $labRequest->id) }}" class="mb-2">
                                @csrf
                                <button class="btn btn-warning w-100"><i class="fas fa-vial"></i> Mark Sample Collected</button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('hospital.lab.complete', $labRequest->id) }}">
                            @csrf
                            <button class="btn btn-primary w-100"><i class="fas fa-play"></i> Start Processing</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white"><i class="fas fa-flask me-1"></i> Results</div>
                <div class="card-body">
                    @if($labRequest->results && $labRequest->results->count() > 0)
                        <table class="table table-striped">
                            <thead>
                                <tr><th>Test</th><th>Parameter</th><th>Result</th><th>Unit</th><th>Range</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @foreach($labRequest->results as $r)
                                    <tr>
                                        <td>{{ $r->test_name }}</td>
                                        <td>{{ $r->parameter ?: '—' }}</td>
                                        <td><strong>{{ $r->result }}</strong></td>
                                        <td>{{ $r->unit ?: '—' }}</td>
                                        <td>{{ $r->reference_range ?: '—' }}</td>
                                        <td>
                                            <span class="badge bg-{{ $r->status === 'normal' ? 'success' : ($r->status === 'critical' ? 'danger' : 'warning') }}">
                                                {{ ucfirst($r->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p class="text-muted mb-0">No results recorded yet.</p>
                    @endif
                </div>
            </div>

            @if($labRequest->status !== 'completed')
                <div class="card mt-3">
                    <div class="card-header bg-primary text-white"><i class="fas fa-plus me-1"></i> Record Results</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('hospital.lab.process', $labRequest->id) }}">
                            @csrf
                            <table class="table">
                                <thead>
                                    <tr><th>Test</th><th>Result</th><th>Unit</th><th>Range</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><input name="results[0][test_name]" class="form-control" required></td>
                                        <td><input name="results[0][result]" class="form-control" required></td>
                                        <td><input name="results[0][unit]" class="form-control"></td>
                                        <td><input name="results[0][reference_range]" class="form-control"></td>
                                        <td>
                                            <select name="results[0][status]" class="form-select" required>
                                                <option value="normal">Normal</option>
                                                <option value="abnormal">Abnormal</option>
                                                <option value="critical">Critical</option>
                                            </select>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                            <button class="btn btn-success"><i class="fas fa-save"></i> Save Results</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection