@extends('layouts.app')

@section('title', 'Book Appointment')

@section('content')
<div class="page-header">
    <h4>Book Medical Appointment</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('student.medical.appointment.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="doctor_id" class="form-label">Select Doctor</label>
                        <select class="form-select @error('doctor_id') is-invalid @enderror"
                                id="doctor_id" name="doctor_id" required>
                            <option value="">Select a Doctor</option>
                            @php
                                // Group doctors by specialization
                                $grouped = $doctors->groupBy('specialization');
                            @endphp
                            @forelse($grouped as $specialization => $specializationDoctors)
                                <optgroup label="{{ $specialization ?? 'General' }}">
                                    @foreach($specializationDoctors as $doctor)
                                        <option value="{{ $doctor->id }}">
                                            Dr. {{ $doctor->first_name }} {{ $doctor->last_name }}
                                            @if($doctor->specialization)
                                                - {{ $doctor->specialization }}
                                            @endif
                                            @if($doctor->is_available)
                                                (Available)
                                            @endif
                                        </option>
                                    @endforeach
                                </optgroup>
                            @empty
                                <option value="">No doctors available</option>
                            @endforelse
                        </select>
                        @error('doctor_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="appointment_date" class="form-label">Appointment Date</label>
                        <input type="date" class="form-control @error('appointment_date') is-invalid @enderror"
                               id="appointment_date" name="appointment_date"
                               min="{{ date('Y-m-d') }}" required>
                        @error('appointment_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label for="appointment_time" class="form-label">Preferred Time</label>
                        <select class="form-select @error('appointment_time') is-invalid @enderror"
                                id="appointment_time" name="appointment_time" required>
                            <option value="">Select Time</option>
                            <option value="09:00">09:00 AM</option>
                            <option value="09:30">09:30 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="10:30">10:30 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="11:30">11:30 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="12:30">12:30 PM</option>
                            <option value="13:00">01:00 PM</option>
                            <option value="13:30">01:30 PM</option>
                            <option value="14:00">02:00 PM</option>
                            <option value="14:30">02:30 PM</option>
                            <option value="15:00">03:00 PM</option>
                            <option value="15:30">03:30 PM</option>
                            <option value="16:00">04:00 PM</option>
                            <option value="16:30">04:30 PM</option>
                        </select>
                        @error('appointment_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>

            <div class="mb-3">
                <label for="complaint" class="form-label">Complaint / Reason for Visit</label>
                <textarea class="form-control @error('complaint') is-invalid @enderror"
                          id="complaint" name="complaint" rows="4"
                          placeholder="Describe your symptoms or reason for visit..." required></textarea>
                @error('complaint')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-calendar-check me-2"></i>Book Appointment
                </button>
                <a href="{{ route('student.medical.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection