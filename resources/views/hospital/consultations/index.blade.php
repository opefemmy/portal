@extends('layouts.app')

@section('title', 'Consultations')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h3 class="mb-0"><i class="fas fa-stethoscope me-2"></i>Consultations</h3>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Visit Type</th>
                            <th>Chief Complaint</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($records as $r)
                            <tr>
                                <td>#{{ $r->id }}</td>
                                <td>{{ optional($r->consultation_date)->format('d M Y, h:i A') ?? $r->created_at->format('d M Y') }}</td>
                                <td>
                                    @if($r->patient)
                                        {{ $r->patient->first_name }} {{ $r->patient->last_name }}
                                    @else
                                        <span class="text-muted">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($r->doctor)
                                        Dr. {{ $r->doctor->first_name }} {{ $r->doctor->last_name }}
                                    @else
                                        <span class="text-muted">Unassigned</span>
                                    @endif
                                </td>
                                <td>{{ ucfirst(str_replace('_',' ', $r->visit_type ?? '')) }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($r->chief_complaint ?? '—', 60) }}</td>
                                <td>
                                    <a href="{{ route('hospital.consultations.show', $r) }}" class="btn btn-sm btn-info" title="View consultation">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No consultations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection