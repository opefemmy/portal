@extends('layouts.app')

@section('title', 'Allocate Hostel Room')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>Allocate Hostel Room</h4></div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.hostels.storeAllocation') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Student</label>
                    <select name="student_id" class="form-select" required>
                        <option value="">-- Select student --</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}" {{ old('student_id') == $st->id ? 'selected' : '' }}>
                                {{ $st->user->name ?? '' }} ({{ $st->matric_number ?? 'no-matric' }})
                            </option>
                        @endforeach
                    </select>
                    @error('student_id') <small class="text-danger">{{ $message }}</small> @enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Session</label>
                    <select name="session_id" class="form-select" required>
                        @foreach($sessions as $s)
                            <option value="{{ $s->id }}" {{ old('session_id', $s->is_current ? $s->id : null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Hostel</label>
                    <select name="hostel_id" class="form-select" required>
                        @foreach($hostels as $h)
                            <option value="{{ $h->id }}" {{ old('hostel_id') == $h->id ? 'selected' : '' }}>{{ $h->name }} ({{ ucfirst($h->gender ?? 'mixed') }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Room ID</label>
                    <input type="number" name="hostel_room_id" class="form-control" value="{{ old('hostel_room_id') }}" placeholder="Room ID — open the hostel to see IDs" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Check-in Date</label>
                    <input type="date" name="check_in_date" class="form-control" value="{{ old('check_in_date', date('Y-m-d')) }}" required>
                </div>
            </div>
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Allocate</button>
                <a href="{{ route('admin.hostels.allocations') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
