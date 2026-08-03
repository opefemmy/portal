@extends('layouts.app')

@section('title', 'Communications')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-envelope me-2"></i>Communications</h3>
    <p class="text-muted">Patient: <strong>{{ $patient->full_name }}</strong></p>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Date</th><th>Channel</th><th>Subject</th><th>Direction</th></tr>
                </thead>
                <tbody>
                    @forelse($communications ?? collect() as $c)
                        <tr>
                            <td>{{ optional($c->created_at)->format('d M Y H:i') ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ strtoupper($c->channel ?? 'sms') }}</span></td>
                            <td>{{ $c->subject ?? '—' }}</td>
                            <td>{{ ucfirst($c->direction ?? 'outbound') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No communications yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if(isset($communications) && method_exists($communications, 'links'))
                <div class="p-3">{{ $communications->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection