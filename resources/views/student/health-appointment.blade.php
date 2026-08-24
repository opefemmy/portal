@extends('layouts.app')

@section('title', 'Book Appointment')

@section('content')
<div class="page-header"><h4><i class="fas fa-calendar-plus me-2"></i>Book Medical Appointment</h4></div>

<div class="card"><div class="card-body">
    <form method="POST" action="{{ route('student.medical.book') }}">
        @csrf
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Preferred Date</label>
                <input type="date" name="appointment_date" class="form-control" value="{{ old('appointment_date', date('Y-m-d', strtotime('+1 day'))) }}" min="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Preferred Time</label>
                <input type="time" name="appointment_time" class="form-control" value="{{ old('appointment_time', '09:00') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Doctor (optional)</label>
                <select name="doctor_id" class="form-select">
                    <option value="">— Any available —</option>
                    @foreach($doctors ?? [] as $d)
                        <option value="{{ $d->id }}">{{ $d->user->name ?? 'Doctor' }} ({{ $d->specialization ?? 'General' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Reason</label>
                <input type="text" name="reason" class="form-control" value="{{ old('reason') }}" placeholder="e.g. routine checkup" required>
            </div>
            <div class="col-md-12">
                <label class="form-label">Symptoms / Notes</label>
                <textarea name="symptoms" class="form-control" rows="3" placeholder="Describe any symptoms you're experiencing…">{{ old('symptoms') }}</textarea>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary"><i class="fas fa-calendar-plus me-2"></i>Book Appointment</button>
            <a href="{{ route('student.medical.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div></div>
@endsection
