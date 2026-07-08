@extends('layouts.app')

@section('title', 'Medical History')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Medical History</h4>
    <a href="{{ route('student.medical.index') }}" class="btn btn-primary">
        <i class="fas fa-arrow-left me-2"></i>Back to Medical Portal
    </a>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Doctor</th>
                    <th>Complaint</th>
                    <th>Diagnosis</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td>{{ $record->consultation_date->format('d M Y') }}</td>
                    <td>Dr. {{ $record->doctor->last_name ?? 'N/A' }}</td>
                    <td>{{ Str::limit($record->chief_complaint, 50) }}</td>
                    <td>
                        @forelse($record->diagnoses as $diagnosis)
                            <span class="badge bg-info me-1">{{ $diagnosis->name }}</span>
                        @empty
                            <span class="text-muted">No diagnosis</span>
                        @endforelse
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#recordModal{{ $record->id }}">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </td>
                </tr>

                <!-- Medical Record Detail Modal -->
                <div class="modal fade" id="recordModal{{ $record->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Medical Record - {{ $record->consultation_date->format('d M Y') }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6><strong>Doctor:</strong> Dr. {{ $record->doctor->first_name ?? '' }} {{ $record->doctor->last_name ?? '' }}</h6>
                                        <p><strong>Complaint:</strong> {{ $record->chief_complaint }}</p>
                                        <p><strong>Vital Signs:</strong></p>
                                        <ul>
                                            <li>Blood Pressure: {{ $record->blood_pressure ?? 'N/A' }}</li>
                                            <li>Temperature: {{ $record->temperature ?? 'N/A' }}</li>
                                            <li>Weight: {{ $record->weight ?? 'N/A' }} kg</li>
                                            <li>Pulse: {{ $record->pulse ?? 'N/A' }} bpm</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Diagnosis:</strong></p>
                                        @forelse($record->diagnoses as $diagnosis)
                                            <span class="badge bg-info mb-2">{{ $diagnosis->name }}</span>
                                        @empty
                                            <p class="text-muted">No diagnosis recorded</p>
                                        @endforelse

                                        <p class="mt-3"><strong>Notes:</strong></p>
                                        <p>{{ $record->notes ?? 'No notes' }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="5" class="text-center">No medical records found</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $records->links() }}
    </div>
</div>
@endsection