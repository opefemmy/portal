@extends('layouts.app')

@section('title', 'Admission Letter')

@section('content')
<div class="container">
    <div class="card shadow-sm print-shadow-none">
        <div class="card-body p-5">
            <div class="text-center mb-4">
                <h2 class="mb-1">Office of the Registrar</h2>
                <h4 class="text-muted">Admission Letter</h4>
            </div>

            <p class="mb-4">
                <strong>Date:</strong> {{ now()->format('d M, Y') }}
            </p>

            <p class="mb-4">
                <strong>{{ $applicant->first_name }} {{ $applicant->surname }}</strong><br>
                Application Number: <code>{{ $applicant->application_number }}</code><br>
                Email: {{ $applicant->email }}<br>
                @if($applicant->phone) Phone: {{ $applicant->phone }} @endif
            </p>

            <p class="mb-3"><strong>Dear {{ $applicant->first_name }},</strong></p>

            <p class="mb-3">
                We are pleased to inform you that you have been offered provisional admission
                into the following programme:
            </p>

            <table class="table table-borderless mb-4">
                <tr>
                    <th width="35%">School</th>
                    <td>{{ $applicant->school->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td>{{ $applicant->department->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Programme</th>
                    <td>{{ $applicant->programme->name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Session</th>
                    <td>{{ $applicant->session->name ?? 'N/A' }}</td>
                </tr>
                @if($student && $student->matric_number)
                <tr>
                    <th>Matric Number</th>
                    <td><code>{{ $student->matric_number }}</code></td>
                </tr>
                @endif
                <tr>
                    <th>Level</th>
                    <td>100 Level</td>
                </tr>
            </table>

            <p class="mb-3">
                Your acceptance fee has been verified. Please bring this letter along with
                your original credentials to the Admissions Office on or before the resumption date
                to complete your registration.
            </p>

            <p class="mb-4">
                Congratulations and welcome aboard.
            </p>

            <div class="mt-5">
                <p class="mb-1">Yours faithfully,</p>
                <br><br>
                <p class="mb-0"><strong>Registrar</strong></p>
            </div>
        </div>

        <div class="card-footer d-flex justify-content-between no-print">
            <a href="{{ route('applicant.dashboard') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
            <button type="button" class="btn btn-primary" onclick="window.print()">
                <i class="fas fa-print me-2"></i>Print
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
@media print {
    .no-print, .main-header, .sidebar, .main-footer { display: none !important; }
    .print-shadow-none { box-shadow: none !important; border: none !important; }
}
</style>
@endpush
@endsection
