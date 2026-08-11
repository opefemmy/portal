@extends('layouts.app')

@section('title', 'Customize Dashboard')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">
            <i class="fas fa-sliders-h me-2"></i>Customize Dashboard
        </h4>
        <p class="text-muted mb-0">
            Pick which widgets <strong>{{ $target->name }}</strong>
            ({{ $role !== '' ? $role : 'no role' }}) sees and in what order.
        </p>
    </div>
    <div>
        <a href="{{ route('admin.dashboard') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-tachometer-alt me-1"></i>Back to Dashboard
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.dashboard-config.update', $target) }}">
            @csrf
            @method('PUT')

            @if(empty($eligible))
                <div class="alert alert-warning mb-0">
                    No widgets are registered for the
                    <code>{{ $role ?: '(unknown)' }}</code> role. The
                    user will continue to see the role default until
                    widgets are added to the registry.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Enabled</th>
                                <th>Widget</th>
                                <th style="width: 140px;">Position</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($eligible as $widgetKey => $def)
                                @php
                                    $row      = $existing->get($widgetKey);
                                    $enabled  = $row ? (bool) $row->is_enabled : true;
                                    $position = $row ? (int) $row->position : (int) $loop->index;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="widgets[{{ $widgetKey }}][is_enabled]"
                                                   value="1"
                                                   id="w-{{ $widgetKey }}-enabled"
                                                   {{ $enabled ? 'checked' : '' }}>
                                            <label class="form-check-label visually-hidden"
                                                   for="w-{{ $widgetKey }}-enabled">
                                                Enable
                                            </label>
                                        </div>
                                    </td>
                                    <td>
                                        <label for="w-{{ $widgetKey }}-enabled"
                                               class="fw-semibold mb-0 d-block">
                                            {{ $def->label }}
                                        </label>
                                        <code class="small text-muted">{{ $widgetKey }}</code>
                                    </td>
                                    <td>
                                        <input type="number"
                                               name="widgets[{{ $widgetKey }}][position]"
                                               id="w-{{ $widgetKey }}-position"
                                               value="{{ $position }}"
                                               min="0"
                                               class="form-control form-control-sm"
                                               style="width: 90px;">
                                    </td>
                                    <td class="text-muted small">
                                        <span class="badge bg-light text-dark border me-1">{{ $def->type }}</span>
                                        @if(empty($def->appliesToRoles))
                                            Visible to all roles
                                        @else
                                            Visible to:
                                            {{ implode(', ', $def->appliesToRoles) }}
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        Lower position renders first.
                        Unchecking a widget removes it from the user's dashboard entirely.
                    </small>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Configuration
                    </button>
                </div>
            @endif
        </form>
    </div>
</div>

@php
    // Show a live, lightweight preview of what each widget would
    // render. Useful to confirm the widget before saving.
@endphp
@if(!empty($eligible))
    <div class="card mt-3">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">
                <i class="fas fa-eye me-2"></i>Preview
            </h5>
        </div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                Each widget below uses live data against the database.
                The order shown matches the registry order, not the
                user's saved position — your position values apply after save.
            </p>
            @foreach($eligible as $widgetKey => $def)
                @php $previewData = ($def->data)(); @endphp
                <div class="mb-3">
                    <h6 class="text-muted">
                        <i class="fas fa-cog me-1"></i>{{ $def->label }}
                        <span class="small">({{ $widgetKey }})</span>
                    </h6>
                    @include($def->partial, ['data' => $previewData, 'label' => $def->label])
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection
