@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Dashboard</h4>
        <p class="text-muted mb-0">Welcome back, {{ auth()->user()->name }}</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @if(auth()->user()->role && in_array(auth()->user()->role->slug, ['super_admin'], true))
            <a href="{{ route('admin.dashboard-config.edit', auth()->id()) }}"
               class="btn btn-sm btn-outline-primary">
                <i class="fas fa-sliders-h me-1"></i>Customize Dashboard
            </a>
        @endif
        <span class="badge bg-primary fs-6">
            <i class="fas fa-calendar me-1"></i>
            Session: {{ $currentSession->name ?? 'Not Set' }}
        </span>
    </div>
</div>

@include('widgets.render', ['widgets' => $widgets])

{{-- Empty-state CTA: super_admin can reach the configurator even when
     no widgets are enabled (the shared widgets.render partial shows a
     generic message in that case). The page-header "Customize
     Dashboard" link is always visible above; this button is a
     redundant convenience specifically for the empty-state. --}}
@if(empty($widgets) && auth()->user()->role && auth()->user()->role->slug === 'super_admin')
    <div class="text-center mb-4">
        <a href="{{ route('admin.dashboard-config.edit', auth()->id()) }}"
           class="btn btn-primary btn-sm">
            <i class="fas fa-sliders-h me-1"></i>Customize Dashboard
        </a>
    </div>
@endif

<!-- Quick Actions -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.users.create') }}" class="btn btn-outline-primary w-100 py-3">
                            <i class="fas fa-user-plus fa-2x d-block mb-2"></i>
                            Add New User
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.courses.create') }}" class="btn btn-outline-success w-100 py-3">
                            <i class="fas fa-book fa-2x d-block mb-2"></i>
                            Add New Course
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.fees.create') }}" class="btn btn-outline-warning w-100 py-3">
                            <i class="fas fa-dollar-sign fa-2x d-block mb-2"></i>
                            Configure Fees
                        </a>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <a href="{{ route('admin.reports') }}" class="btn btn-outline-info w-100 py-3">
                            <i class="fas fa-chart-bar fa-2x d-block mb-2"></i>
                            View Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection