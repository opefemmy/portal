@extends('layouts.app')

@section('title', 'Clinical Notes — ' . $patient->full_name)

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="mb-0"><i class="fas fa-notes-medical me-2"></i>Clinical Notes</h3>
            <small class="text-muted">{{ $patient->full_name }} · {{ $patient->patient_number }}</small>
        </div>
        <a href="{{ route('hospital.patients.show', $patient->id) }}" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Patient
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            @forelse($notes as $n)
                <div class="border-start border-3 border-secondary ps-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <strong>{{ strtoupper($n->note_type) }} note</strong>
                        <small class="text-muted">{{ optional($n->created_at)->format('d M Y H:i') }}</small>
                    </div>
                    <small class="text-muted">{{ $n->staff?->full_name ?? '—' }}</small>
                    @if($n->signed_at)
                        <span class="badge bg-success ms-2">
                            <i class="fas fa-signature"></i> Signed by {{ $n->signed_by_name }}
                        </span>
                    @else
                        <span class="badge bg-warning text-dark ms-2">Draft</span>
                    @endif
                    <div class="mt-1 small">
                        @if($n->subjective)<div><strong>S:</strong> {{ $n->subjective }}</div>@endif
                        @if($n->objective)<div><strong>O:</strong> {{ $n->objective }}</div>@endif
                        @if($n->assessment)<div><strong>A:</strong> {{ $n->assessment }}</div>@endif
                        @if($n->plan)<div><strong>P:</strong> {{ $n->plan }}</div>@endif
                    </div>
                </div>
            @empty
                <p class="text-muted mb-0">No clinical notes yet.</p>
            @endforelse

            {{ $notes->links() }}
        </div>
    </div>
</div>
@endsection