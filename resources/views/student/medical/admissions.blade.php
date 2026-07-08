@extends('layouts.app')

@section('title', 'My Admissions')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>My Admissions</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Medical Portal
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Admission Date</th>
                    <th>Discharge Date</th>
                    <th>Ward</th>
                    <th>Doctor</th>
                    <th>Reason</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($admissions as $admission)
                <tr>
                    <td>{{ $admission->admission_date->format('d M Y') }}</td>
                    <td>{{ $admission->discharge_date ? $admission->discharge_date->format('d M Y') : 'N/A' }}</td>
                    <td>{{ $admission->bed->ward->name ?? 'N/A' }}</td>
                    <td>Dr. {{ $admission->doctor->last_name ?? 'N/A' }}</td>
                    <td>{{ Str::limit($admission->reason, 30) }}</td>
                    <td>
                        @switch($admission->status)
                            @case('admitted')
                                <span class="badge bg-primary">Admitted</span>
                                @break
                            @case('discharged')
                                <span class="badge bg-success">Discharged</span>
                                @break
                            @case('transferred')
                                <span class="badge bg-info">Transferred</span>
                                @break
                            @case('deceased')
                                <span class="badge bg-danger">Deceased</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ $admission->status }}</span>
                        @endswitch
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#admissionModal{{ $admission->id }}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </td>
                </tr>

                <!-- Admission Detail Modal -->
                <div class="modal fade" id="admissionModal{{ $admission->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Admission Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <p><strong>Admission Date:</strong> {{ $admission->admission_date->format('d M Y') }}</p>
                                        <p><strong>Discharge Date:</strong> {{ $admission->discharge_date ? $admission->discharge_date->format('d M Y') : 'N/A' }}</p>
                                        <p><strong>Ward:</strong> {{ $admission->bed->ward->name ?? 'N/A' }}</p>
                                        <p><strong>Bed Number:</strong> {{ $admission->bed->bed_number ?? 'N/A' }}</p>
                                        <p><strong>Attending Doctor:</strong> Dr. {{ $admission->doctor->first_name ?? '' }} {{ $admission->doctor->last_name ?? '' }}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Status:</strong>
                                            @switch($admission->status)
                                                @case('admitted')
                                                    <span class="badge bg-primary">Admitted</span>
                                                    @break
                                                @case('discharged')
                                                    <span class="badge bg-success">Discharged</span>
                                                    @break
                                                @default
                                                    <span class="badge bg-secondary">{{ $admission->status }}</span>
                                            @endswitch
                                        </p>
                                        <p><strong>Admission Reason:</strong></p>
                                        <p>{{ $admission->reason }}</p>
                                    </div>
                                </div>
                                @if($admission->diagnosis)
                                <div class="mt-3">
                                    <p><strong>Diagnosis:</strong></p>
                                    <p>{{ $admission->diagnosis }}</p>
                                </div>
                                @endif
                                @if($admission->treatment)
                                <div class="mt-3">
                                    <p><strong>Treatment Given:</strong></p>
                                    <p>{{ $admission->treatment }}</p>
                                </div>
                                @endif
                                @if($admission->discharge_notes)
                                <div class="mt-3">
                                    <p><strong>Discharge Notes:</strong></p>
                                    <p>{{ $admission->discharge_notes }}</p>
                                </div>
                                @endif
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No admissions found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $admissions->links() }}
    </div>
</div>
@endsection