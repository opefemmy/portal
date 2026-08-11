@extends('layouts.app')

@section('title', 'Record Requests')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-inbox me-2"></i>Record Requests</h4>
    <ul class="breadcrumb">
        <li class="breadcrumb-item"><a href="{{ route('hospital.records.index') }}">Records</a></li>
        <li class="breadcrumb-item active">Requests</li>
    </ul>
</div>

<div class="card mb-3">
    <div class="card-header">
        <form method="GET" class="d-inline">
            <label class="me-2">Filter:</label>
            <select name="status" onchange="this.form.submit()" class="form-select d-inline w-auto">
                @foreach(['pending','approved','fulfilled','rejected'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Requested</th>
                    <th>Patient</th>
                    <th>Requested by</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $r)
                    <tr>
                        <td>{{ optional($r->requested_at)->format('d M Y H:i') }}</td>
                        <td>{{ optional($r->patient)->full_name ?? '—' }}</td>
                        <td>{{ optional($r->requestedByUser)->name ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($r->reason, 50) }}</td>
                        <td>
                            @switch($r->status)
                                @case('pending')  <span class="badge bg-warning">Pending</span> @break
                                @case('approved') <span class="badge bg-info">Approved</span> @break
                                @case('fulfilled')<span class="badge bg-success">Fulfilled</span> @break
                                @case('rejected') <span class="badge bg-danger">Rejected</span> @break
                                @default          <span class="badge bg-secondary">{{ $r->status }}</span>
                            @endswitch
                        </td>
                        <td>
                            @if(in_array($r->status, ['pending','approved']))
                                <form method="POST" action="{{ route('hospital.records.requests.fulfill', $r) }}" class="d-inline">
                                    @csrf
                                    <button class="btn btn-sm btn-success"><i class="fas fa-check"></i> Fulfill</button>
                                </form>
                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reject{{ $r->id }}">
                                    <i class="fas fa-times"></i> Reject
                                </button>

                                <div class="modal fade" id="reject{{ $r->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('hospital.records.requests.reject', $r) }}">
                                            @csrf
                                            <div class="modal-content">
                                                <div class="modal-header"><h5>Reject request</h5></div>
                                                <div class="modal-body">
                                                    <textarea name="notes" class="form-control" rows="3" required placeholder="Reason for rejection"></textarea>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @else
                                <small class="text-muted">{{ $r->status }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No requests in this view.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $requests->withQueryString()->links() }}
    </div>
</div>
@endsection