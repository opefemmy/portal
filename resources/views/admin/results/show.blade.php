@extends('layouts.app')

@section('title', 'View Result')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Result Details</h4>
    <a href="{{ route('admin.results.index') }}" class="btn btn-secondary">
        <i class="fas fa-arrow-left me-2"></i>Back
    </a>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-borderless">
            <tr><th width="30%">Student:</th><td>{{ $result->studentCourse->student->matric_number ?? 'N/A' }} - {{ $result->studentCourse->student->user->name ?? 'N/A' }}</td></tr>
            <tr><th>Course:</th><td>{{ $result->course->code ?? 'N/A' }} - {{ $result->course->title ?? 'N/A' }}</td></tr>
            <tr><th>CA1:</th><td>{{ $result->ca1 ?? 0 }}</td></tr>
            <tr><th>CA2:</th><td>{{ $result->ca2 ?? 0 }}</td></tr>
            <tr><th>Exam:</th><td>{{ $result->exam ?? 0 }}</td></tr>
            <tr><th>Total:</th><td><strong>{{ $result->total_score ?? 0 }}</strong></td></tr>
            <tr><th>Grade:</th><td><span class="badge bg-{{ ($result->grade ?? 'F') == 'A' ? 'success' : 'warning' }}">{{ $result->grade ?? 'N/A' }}</span></td></tr>
            <tr><th>Status:</th><td>{{ ucfirst($result->status ?? 'pending') }}</td></tr>
            <tr><th>Approved By:</th><td>{{ $result->approvedBy->name ?? 'N/A' }}</td></tr>
            <tr><th>Approved At:</th><td>{{ $result->approved_at ? $result->approved_at->format('d M Y h:i A') : 'N/A' }}</td></tr>
        </table>
    </div>
</div>
@endsection