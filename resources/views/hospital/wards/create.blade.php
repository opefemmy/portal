@extends('layouts.app')

@section('title', 'New Ward')

@section('content')
<div class="page-header">
    <h4 class="page-title"><i class="fas fa-plus me-2"></i>New Ward</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('hospital.wards.store') }}">
            @csrf
            @include('hospital.wards._form')
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save</button>
            <a href="{{ route('hospital.wards.index') }}" class="btn btn-link">Cancel</a>
        </form>
    </div>
</div>
@endsection