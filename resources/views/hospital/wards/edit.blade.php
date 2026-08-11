@extends('layouts.app')

@section('title', 'Edit Ward')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-edit me-2"></i>Edit Ward</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('hospital.wards.update', $ward) }}">
            @csrf @method('PUT')
            @include('hospital.wards._form')
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Update</button>
            <a href="{{ route('hospital.wards.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection