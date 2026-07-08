@extends('layouts.app')

@section('title', 'Module Scanner')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-cubes me-2"></i>Module Scanner</h4>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">System Modules</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($modules as $name => $exists)
                <tr>
                    <td>{{ $name }}</td>
                    <td>
                        <span class="badge bg-{{ $exists ? 'success' : 'danger' }}">
                            {{ $exists ? 'Active' : 'Missing' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h3>{{ count($controllers) }}</h3>
                <p class="text-muted">Controllers</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h3>{{ count($services) }}</h3>
                <p class="text-muted">Services</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <h3>{{ count($models) }}</h3>
                <p class="text-muted">Models</p>
            </div>
        </div>
    </div>
</div>
@endsection