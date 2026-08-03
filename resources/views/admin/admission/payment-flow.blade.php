@extends('layouts.app')

@section('title', 'Admission Payment Flow')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-money-bill-wave me-2"></i>Admission Payment Flow</h4>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('admin.admission.payment-flow.update') }}">
    @csrf
    @method('PUT')

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-coins me-2"></i>Fee Amounts</h5>
        </div>
        <div class="card-body">
            <p class="text-muted">
                Default amount is read from the <a href="{{ route('admin.payment-types.index') }}">Payment Types</a>
                catalogue. A live override here wins until cleared — useful for promo pricing
                without editing the catalogue.
            </p>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Fee</th>
                            <th>Catalogue Amount (₦)</th>
                            <th>Live Override (₦)</th>
                            <th>Active</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rows as $row)
                            <tr>
                                <td>
                                    <strong>{{ $row['label'] }}</strong><br>
                                    <small class="text-muted"><code>{{ $row['code'] }}</code> &middot; <code>{{ $row['purpose'] }}</code></small>
                                </td>
                                <td>
                                    @if($row['type'])
                                        <a href="{{ route('admin.payment-types.edit', $row['type']) }}">
                                            ₦{{ number_format($row['defaultAmount'], 2) }}
                                        </a>
                                    @else
                                        <span class="text-danger">Payment type missing</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" min="0" step="0.01"
                                           name="overrides[{{ $row['purpose'] }}]"
                                           value="{{ $row['overrideAmount'] > 0 ? $row['overrideAmount'] : '' }}"
                                           placeholder="No override"
                                           class="form-control form-control-sm">
                                </td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input type="hidden" name="is_active[{{ $row['purpose'] }}]" value="0">
                                        <input class="form-check-input" type="checkbox"
                                               name="is_active[{{ $row['purpose'] }}]" value="1"
                                               {{ $row['isActive'] ? 'checked' : '' }}>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i>Gates</h5>
        </div>
        <div class="card-body">
            <div class="form-check form-switch mb-3">
                <input type="hidden" name="form_open" value="0">
                <input class="form-check-input" type="checkbox" id="form_open" name="form_open" value="1"
                       {{ $formOpen ? 'checked' : '' }}>
                <label class="form-check-label" for="form_open">
                    Admission form is <strong>open</strong>
                    <small class="text-muted d-block">When off, applicants see a "form closed" page.</small>
                </label>
            </div>
            <div class="form-check form-switch">
                <input type="hidden" name="require_fee" value="0">
                <input class="form-check-input" type="checkbox" id="require_fee" name="require_fee" value="1"
                       {{ $requireFee ? 'checked' : '' }}>
                <label class="form-check-label" for="require_fee">
                    Require application fee before form opens
                    <small class="text-muted d-block">Off = applicants can apply without paying first (registrar must still admit).</small>
                </label>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save me-2"></i>Save Changes
    </button>
    <a href="{{ route('admin.payment-types.index') }}" class="btn btn-outline-secondary">
        <i class="fas fa-list me-1"></i> Manage Payment Types
    </a>
</form>
@endsection
