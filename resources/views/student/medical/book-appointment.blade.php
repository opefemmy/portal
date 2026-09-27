@extends('layouts.app')

@section('title', 'Book Appointment')

@section('content')
<div class="page-header">
    <h4>Book Medical Appointment</h4>
    <p class="text-muted">A doctor will be automatically assigned to your appointment based on current availability.</p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('student.medical.appointment.store') }}">
            @csrf

            <div class="row">
                <div class="col-md-6">
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
            </div>

            <div class="mb-3">
                <label for="symptoms" class="form-label">Symptoms / Reason for Visit</label>
                <textarea class="form-control @error('symptoms') is-invalid @enderror"
                          id="symptoms" name="symptoms" rows="4"
                          placeholder="Describe your symptoms or reason for visit..." required></textarea>
                @error('symptoms')
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
