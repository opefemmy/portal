@extends('layouts.app')

@section('title', 'My Application')

@section('content')
<div class="page-header">
    <h4>My Application</h4>
</div>

<div class="card">
    <div class="card-body">
        <h5>Application Details</h5>
        <table class="table">
            <tr>
                <th>Application Number:</th>
                <td>{{ $applicant->application_number }}</td>
            </tr>
            <tr>
                <th>Status:</th>
                <td>
                    <span class="badge bg-{{ $applicant->status === 'admitted' ? 'success' : ($applicant->status === 'rejected' ? 'danger' : 'warning') }}">
                        {{ ucfirst($applicant->status) }}
                    </span>
                </td>
            </tr>
            <tr>
                <th>School:</th>
                <td>{{ $applicant->school->name }}</td>
            </tr>
            <tr>
                <th>Department:</th>
                <td>{{ $applicant->department->name }}</td>
            </tr>
            <tr>
                <th>Programme:</th>
                <td>{{ $applicant->programme->name }}</td>
            </tr>
            <tr>
                <th>Session:</th>
                <td>{{ $applicant->session->name }}</td>
            </tr>
        </table>
    </div>
</div>
@endsection