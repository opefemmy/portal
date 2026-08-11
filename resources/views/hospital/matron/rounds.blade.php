@extends('layouts.app')

@section('title', 'Ward Rounds')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h4 class="page-title"><i class="fas fa-stethoscope me-2"></i>Ward Rounds</h4>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('hospital.matron.dashboard') }}">Matron</a></li>
                <li class="breadcrumb-item active">Rounds</li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Ward</label>
                <select name="ward_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All wards</option>
                    @foreach($wards as $ward)
                        <option value="{{ $ward->id }}" @selected(request('ward_id') == $ward->id)>{{ $ward->name }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Patient</th>
                    <th>Ward / Bed</th>
                    <th>Doctor</th>
                    <th>Reason</th>
                    <th>Admitted</th>
                    <th>Length of Stay</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($inpatients as $adm)
                <tr>
                    <td><strong>{{ optional($adm->patient)->full_name }}</strong><br>
                        <small class="text-muted">{{ optional($adm->patient)->patient_number }}</small>
                    </td>
                    <td>{{ optional($adm->bed->ward)->name }} / {{ optional($adm->bed)->bed_number }}</td>
                    <td>Dr. {{ optional($adm->doctor)->last_name ?? 'TBA' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit($adm->reason, 40) }}</td>
                    <td>{{ $adm->admission_date->format('d M Y H:i') }}</td>
                    <td><span class="badge bg-info">{{ $adm->admission_date->diffInDays(now()) }}d</span></td>
                    <td>
                        <a href="{{ route('hospital.patients.timeline', $adm->patient_id) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No inpatients.</td></tr>
                @endforelse
            </tbody>
        </table>
        {{ $inpatients->withQueryString()->links() }}
    </div>
</div>
@endsection