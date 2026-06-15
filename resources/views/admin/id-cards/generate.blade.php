@extends('layouts.app')

@section('title', 'Generate ID Card')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Student ID Card</h4>
    <div>
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print me-2"></i>Print
        </button>
        <a href="{{ route('admin.id-cards.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card" id="id-card">
            <div class="card-body p-0">
                <!-- ID Card Design -->
                <div class="id-card" style="background: linear-gradient(135deg, #1a237e, #6a1b9a); padding: 30px; color: white; border-radius: 10px;">
                    <div class="text-center mb-3">
                        <i class="fas fa-university fa-3x"></i>
                        <h4 class="mt-2">Institution Management Portal</h4>
                        <p class="mb-0">Student Identity Card</p>
                    </div>

                    <div class="text-center mb-3">
                        @if($student->user->passport)
                        <img src="{{ asset('storage/' . $student->user->passport) }}"
                             alt="Passport"
                             style="width: 120px; height: 120px; border-radius: 10px; border: 3px solid white;">
                        @else
                        <div style="width: 120px; height: 120px; border-radius: 10px; border: 3px solid white; background: #ccc; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-user fa-3x text-white"></i>
                        </div>
                        @endif
                    </div>

                    <div class="text-center">
                        <h5>{{ $student->user->name ?? 'N/A' }}</h5>
                        <p class="mb-1"><strong>Matric No:</strong> {{ $student->matric_number ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Department:</strong> {{ $student->department->name ?? 'N/A' }}</p>
                        <p class="mb-1"><strong>Programme:</strong> {{ $student->programme->name ?? 'N/A' }}</p>
                        <p class="mb-0"><strong>Level:</strong> {{ $student->levelDisplay }}</p>
                    </div>

                    <div class="text-center mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.3);">
                        <small>This card is property of the institution. Please return upon graduation.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #id-card, #id-card * {
        visibility: visible;
    }
    #id-card {
        position: absolute;
        left: 0;
        top: 0;
    }
    .page-header, .btn {
        display: none;
    }
}
</style>
@endsection