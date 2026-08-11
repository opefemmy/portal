@extends('layouts.app')

@section('title', 'Hospital Staff')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-user-md me-2"></i>Hospital Staff</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.admin.dashboard') }}">Admin</a></li>
                <li class="breadcrumb-item active">Staff</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-2">
            <div class="col-md-3">
                <select name="staff_type" class="form-select" onchange="this.form.submit()">
                    <option value="">All roles</option>
                    @foreach($types as $t)
                        <option value="{{ $t }}" @selected(request('staff_type') === $t)>{{ ucfirst(str_replace('_',' ',$t)) }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Name</th><th>Type</th><th>Phone</th><th>Email</th>
                    <th>Status</th><th>Availability</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($staff as $s)
                <tr>
                    <td><strong>{{ $s->full_name }}</strong><br><small class="text-muted">{{ $s->staff_number }}</small></td>
                    <td><span class="badge bg-secondary">{{ ucfirst(str_replace('_',' ',$s->staff_type ?? '—')) }}</span></td>
                    <td>{{ $s->phone ?? '—' }}</td>
                    <td>{{ $s->email ?? '—' }}</td>
                    <td>
                        @if($s->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        @if($s->is_available)
                            <span class="badge bg-info">On Call</span>
                        @else
                            <span class="badge bg-light text-dark">Off</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @can('staff.edit')
                        <form method="POST" action="{{ route('hospital.admin.staff.toggle', $s) }}" class="d-inline">
                            @csrf
                            <button class="btn btn-sm btn-outline-{{ $s->is_available ? 'warning' : 'success' }}">
                                <i class="fas fa-power-off"></i> {{ $s->is_available ? 'Off' : 'On' }}
                            </button>
                        </form>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No staff records.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $staff->withQueryString()->links() }}
    </div>
</div>
@endsection