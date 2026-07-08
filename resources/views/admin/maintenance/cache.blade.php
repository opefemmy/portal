@extends('layouts.app')

@section('title', 'Cache Manager')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-broom me-2"></i>Cache Manager</h4>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Clear Caches</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.maintenance.cache.clear') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        <i class="fas fa-broom me-2"></i>Clear All Caches
                    </button>
                </form>

                <hr>

                <form method="POST" action="{{ route('admin.maintenance.optimize') }}">
                    @csrf
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-rocket me-2"></i>Optimize System
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Cache Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td>Default Driver</td>
                        <td><code>{{ config('cache.default') }}</code></td>
                    </tr>
                    <tr>
                        <td>Store</td>
                        <td><code>{{ config('cache.stores.file.driver') ?? 'file' }}</code></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection