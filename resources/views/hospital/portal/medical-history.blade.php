@extends('layouts.app')

@section('title', 'Medical History')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-notes-medical me-2"></i>Medical History</h3>
    <p class="text-muted">Patient: <strong>{{ $patient->full_name }}</strong> ({{ $patient->patient_number }})</p>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Visit Date</th><th>Type</th><th>Diagnosis</th><th>Treatment</th></tr>
                </thead>
                <tbody>
                    @forelse($patient->visits ?? collect() as $v)
                        <tr>
                            <td>{{ optional($v->visit_date)->format('d M Y') ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($v->visit_type ?? 'visit') }}</span></td>
                            <td>{{ $v->diagnosis ?? '—' }}</td>
                            <td>{{ Str::limit($v->treatment ?? '—', 60) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No medical history on record.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection