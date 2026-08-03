@extends('layouts.app')

@section('title', 'Edit Patient — ' . $patient->full_name)

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-edit me-2"></i>Edit Patient</h3>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('hospital.patients.update', $patient->id) }}">
        @csrf @method('PUT')
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-2">
                        <label class="form-label">First Name *</label>
                        <input class="form-control" name="first_name" value="{{ old('first_name', $patient->first_name) }}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Last Name *</label>
                        <input class="form-control" name="last_name" value="{{ old('last_name', $patient->last_name) }}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Gender *</label>
                        <select name="gender" class="form-select" required>
                            <option value="male" {{ $patient->gender === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ $patient->gender === 'female' ? 'selected' : '' }}>Female</option>
                        </select>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Date of Birth *</label>
                        <input type="date" class="form-control" name="date_of_birth" value="{{ old('date_of_birth', optional($patient->date_of_birth)->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Phone *</label>
                        <input class="form-control" name="phone" value="{{ old('phone', $patient->phone) }}" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" value="{{ old('email', $patient->email) }}">
                    </div>
                    <div class="col-md-12 mb-2">
                        <label class="form-label">Address *</label>
                        <textarea class="form-control" name="address" rows="2" required>{{ old('address', $patient->address) }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer d-flex justify-content-between">
                <a href="{{ route('hospital.patients.show', $patient->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </form>
</div>
@endsection