@extends('layouts.app')

@section('title', 'Record Search')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-search me-2"></i>Record Search</h4>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hospital.records.index') }}">Records</a></li>
        <li class="breadcrumb-item active">Search</li>
    </ul>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET">
            <div class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label">Search by name, patient number, phone, email, or blood group</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" autofocus>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Search</button>
                </div>
            </div>
        </form>
    </div>
</div>

@if($q)
<div class="card">
    <div class="card-header">
        <h5 class="card-title">{{ $results->count() }} result(s) for "{{ $q }}"</h5>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead><tr><th>Patient</th><th>Number</th><th>Phone</th><th>Blood</th><th>Status</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($results as $p)
                    <tr>
                        <td><strong>{{ $p->full_name }}</strong></td>
                        <td><code>{{ $p->patient_number }}</code></td>
                        <td>{{ $p->phone ?? '—' }}</td>
                        <td>{{ $p->blood_group ?? '—' }}</td>
                        <td>
                            @if($p->archived_at)
                                <span class="badge bg-secondary">Archived</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('hospital.records.show', $p) }}" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-eye"></i> Open
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-3">No patients match.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection