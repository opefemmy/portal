@extends('layouts.app')

@section('title', 'Add Exam Slot')

@section('content')
<div class="page-header"><h4><i class="fas fa-plus me-2"></i>Add Exam Slot</h4></div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.exam-timetable.store') }}">
            @csrf
            @include('admin.exam-timetable._form', ['timetable' => null])
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Exam Slot</button>
                <a href="{{ route('admin.exam-timetable.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
