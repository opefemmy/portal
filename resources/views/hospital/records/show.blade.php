@extends('layouts.app')

@section('title', $patient->full_name . ' — Chart')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title">
                <i class="fas fa-folder-open me-2"></i>{{ $patient->full_name }}
                @if($patient->archived_at)
                    <span class="badge bg-secondary ms-2">Archived</span>
                @endif
            </h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.records.index') }}">Records</a></li>
                <li class="breadcrumb-item active">{{ $patient->patient_number }}</li>
            </ul>
        </div>
        <div class="col-auto float-end ms-auto d-flex gap-2">
            @if(! $patient->archived_at)
                <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#archiveModal">
                    <i class="fas fa-archive me-1"></i> Archive
                </button>
            @else
                <form method="POST" action="{{ route('hospital.records.unarchive', $patient) }}">
                    @csrf
                    <button class="btn btn-outline-success"><i class="fas fa-undo me-1"></i> Unarchive</button>
                </form>
            @endif
            <button class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#transferModal">
                <i class="fas fa-exchange-alt me-1"></i> Transfer
            </button>
        </div>
    </div>
</div>

<!-- Demographics -->
<div class="card mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-3"><strong>Patient #:</strong> {{ $patient->patient_number }}</div>
            <div class="col-md-3"><strong>Gender:</strong> {{ ucfirst($patient->gender ?? '—') }}</div>
            <div class="col-md-3"><strong>Blood Group:</strong> {{ $patient->blood_group ?? '—' }}</div>
            <div class="col-md-3"><strong>Genotype:</strong> {{ $patient->genotype ?? '—' }}</div>
            <div class="col-md-3"><strong>DOB:</strong> {{ optional($patient->date_of_birth)->format('d M Y') ?? '—' }}</div>
            <div class="col-md-3"><strong>Phone:</strong> {{ $patient->phone ?? '—' }}</div>
            <div class="col-md-3"><strong>Email:</strong> {{ $patient->email ?? '—' }}</div>
            <div class="col-md-3"><strong>Next of Kin:</strong> {{ $patient->next_of_kin_name ?? '—' }}</div>
        </div>
    </div>
</div>

<!-- Tabs: clinical summary + audit + transfers -->
<ul class="nav nav-tabs" id="recordTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#summary" type="button">
            <i class="fas fa-notes-medical me-1"></i> Clinical Summary
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#audit" type="button">
            <i class="fas fa-history me-1"></i> Audit Trail
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#transfers" type="button">
            <i class="fas fa-exchange-alt me-1"></i> Transfers
        </button>
    </li>
</ul>

<div class="tab-content border border-top-0 p-3 bg-white">
    <div class="tab-pane fade show active" id="summary">
        @include('hospital.patients.timeline', ['patient' => $patient])
    </div>
    <div class="tab-pane fade" id="audit">
        <h5>Recent access</h5>
        <table class="table table-sm">
            <thead><tr><th>When</th><th>User</th><th>Role</th><th>Action</th></tr></thead>
            <tbody>
                @forelse($recentAudit as $row)
                    <tr>
                        <td>{{ $row->created_at->format('d M Y H:i') }}</td>
                        <td>{{ optional($row->user)->name ?? '—' }}</td>
                        <td>{{ $row->user_role ?? '—' }}</td>
                        <td><code>{{ $row->action }}</code></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted text-center">No audit events yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="tab-pane fade" id="transfers">
        <h5>Transfer history</h5>
        <table class="table table-sm">
            <thead><tr><th>When</th><th>To</th><th>Reason</th><th>By</th><th>Notes</th></tr></thead>
            <tbody>
                @forelse($transfers as $t)
                    <tr>
                        <td>{{ optional($t->transferred_at)->format('d M Y H:i') }}</td>
                        <td>{{ $t->transfer_to }}</td>
                        <td>{{ ucfirst(str_replace('_',' ', $t->transfer_reason ?? '—')) }}</td>
                        <td>{{ optional($t->transferredByUser)->name ?? '—' }}</td>
                        <td>{{ \Illuminate\Support\Str::limit($t->notes, 60) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted text-center">No transfers on record.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Archive modal -->
<div class="modal fade" id="archiveModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('hospital.records.archive', $patient) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5>Archive chart</h5></div>
                <div class="modal-body">
                    <p>Are you sure you want to archive <strong>{{ $patient->full_name }}</strong>? This will mark the chart as archived. It will remain readable for legal retention but will not appear in active queues.</p>
                    <textarea name="archive_reason" class="form-control" rows="3" placeholder="Reason (optional)"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Archive</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Transfer modal -->
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('hospital.records.transfer', $patient) }}">
            @csrf
            <div class="modal-content">
                <div class="modal-header"><h5>Log a transfer</h5></div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Transfer to</label>
                        <input type="text" name="transfer_to" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <select name="transfer_reason" class="form-select">
                            <option value="internal">Internal</option>
                            <option value="external_facility">External facility</option>
                            <option value="court_order">Court order</option>
                            <option value="insurance">Insurance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Log transfer</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection