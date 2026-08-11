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

@php
    // Group widgets by type so stat tiles fit 4-per-row (col-xl-3) and
    // tables fit 2-per-row (col-lg-6). The order the resolver returns
    // is the order the user sees.
    $statWidgets   = [];
    $tableWidgets  = [];
    $otherWidgets  = [];
    foreach ($widgets as $entry) {
        $type = $entry['definition']->type;
        if ($type === 'stat') {
            $statWidgets[] = $entry;
        } elseif ($type === 'table') {
            $tableWidgets[] = $entry;
        } else {
            $otherWidgets[] = $entry;
        }
    }
@endphp

{{-- Stat tiles: render in groups of 4 per row --}}
@if(!empty($statWidgets))
    @foreach(array_chunk($statWidgets, 4) as $rowGroup)
        <div class="row mb-4">
            @foreach($rowGroup as $w)
                @include($w['definition']->partial, ['data' => $w['data'], 'label' => $w['definition']->label])
            @endforeach
        </div>
    @endforeach
@endif

{{-- Tables: render in one wide row, two-per-row via the partial's col-lg-6 --}}
@if(!empty($tableWidgets))
    <div class="row">
        @foreach($tableWidgets as $w)
            @include($w['definition']->partial, ['data' => $w['data']])
        @endforeach
    </div>
@endif

{{-- Anything else (future widget types) renders in its own row --}}
@if(!empty($otherWidgets))
    <div class="row mb-4">
        @foreach($otherWidgets as $w)
            @includeIf($w['definition']->partial, ['data' => $w['data'], 'label' => $w['definition']->label])
        @endforeach
    </div>
@endif

@if(empty($widgets))
    <div class="card">
        <div class="card-body text-center text-muted py-5">
            <i class="fas fa-sliders-h fa-2x mb-3 d-block"></i>
            <p class="mb-2">No widgets are enabled for your dashboard yet.</p>
            @if(auth()->user()->role && auth()->user()->role->slug === 'super_admin')
                <a href="{{ route('admin.dashboard-config.edit', auth()->id()) }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-sliders-h me-1"></i>Customize Dashboard
                </a>
            @endif
        </div>
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