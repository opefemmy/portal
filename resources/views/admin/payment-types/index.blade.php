@extends('layouts.app')

@section('title', 'Payment Types')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Payment Types</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
        <i class="fas fa-plus me-2"></i>Add Payment Type
    </button>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Priority</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Amount</th>
                        <th>Channel</th>
                        <th>Purpose</th>
                        <th>Audience</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($paymentTypes as $type)
                    <tr>
                        <td>{{ $type->priority }}</td>
                        <td>{{ $type->name }}</td>
                        <td><code>{{ $type->code }}</code></td>
                        <td>₦{{ number_format($type->amount, 2) }}</td>
                        <td>{{ ucfirst($type->payment_channel) }}</td>
                        <td>
                            @if($type->purpose)
                                <span class="badge bg-light text-dark">
                                    {{ \App\Models\PaymentType::getPurposes()[$type->purpose] ?? ucfirst($type->purpose) }}
                                </span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @php
                                $audienceClass = match($type->audience ?? 'both') {
                                    'applicant' => 'bg-warning text-dark',
                                    'student'   => 'bg-info text-dark',
                                    'both'      => 'bg-secondary',
                                    default     => 'bg-secondary',
                                };
                                $audienceLabel = \App\Models\PaymentType::getAudiences()[$type->audience ?? 'both'] ?? ucfirst($type->audience ?? 'both');
                            @endphp
                            <span class="badge {{ $audienceClass }}">{{ $audienceLabel }}</span>
                        </td>
                        <td>
                            @if($type->is_active)
                            <span class="badge bg-success">Active</span>
                            @else
                            <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $type->id }}">
                                <i class="fas fa-edit"></i>
                            </button>
                            <form action="{{ route('admin.payment-types.destroy', $type) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editModal{{ $type->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('admin.payment-types.update', $type) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Payment Type</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ $type->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Code</label>
                                            <input type="text" name="code" class="form-control" value="{{ $type->code }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control">{{ $type->description }}</textarea>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Amount (₦)</label>
                                            <input type="number" name="amount" class="form-control" value="{{ $type->amount }}" required min="0" step="0.01">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Payment Channel</label>
                                            <select name="payment_channel" class="form-select" required>
                                                <option value="external" {{ $type->payment_channel == 'external' ? 'selected' : '' }}>External</option>
                                                <option value="internal" {{ $type->payment_channel == 'internal' ? 'selected' : '' }}>Internal</option>
                                                <option value="both" {{ $type->payment_channel == 'both' ? 'selected' : '' }}>Both</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Purpose</label>
                                            <select name="purpose" class="form-select">
                                                <option value="">— None —</option>
                                                @foreach(\App\Models\PaymentType::getPurposes() as $value => $label)
                                                    <option value="{{ $value }}" {{ $type->purpose === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Audience <span class="text-danger">*</span></label>
                                            <select name="audience" class="form-select" required>
                                                @foreach(\App\Models\PaymentType::getAudiences() as $value => $label)
                                                    <option value="{{ $value }}" {{ ($type->audience ?? 'both') === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted">
                                                <strong>Applicant only</strong> hides this type from the public online-payment selector and from any student-facing page.
                                                <strong>Student only</strong> hides it from applicants.
                                            </small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Priority</label>
                                            <input type="number" name="priority" class="form-control" value="{{ $type->priority }}" min="1">
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="is_active" class="form-check-input" id="edit_active{{ $type->id }}" {{ $type->is_active ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_active{{ $type->id }}">Active</label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input type="checkbox" name="requires_payment" class="form-check-input" id="edit_pay{{ $type->id }}" {{ $type->requires_payment ? 'checked' : '' }}>
                                                <label class="form-check-label" for="edit_pay{{ $type->id }}">Requires Payment</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4">No payment types configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('admin.payment-types.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Add Payment Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($errors->any())
                        <div class="alert alert-danger py-2 small">
                            <strong>Please fix the errors below and try again.</strong>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="e.g., Compulsory Fee, Convocation Fee" required>
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code"
                               class="form-control @error('code') is-invalid @enderror"
                               value="{{ old('code') }}"
                               placeholder="e.g., COMP_FEE, CONVOCATION" required>
                        @error('code')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Optional description">{{ old('description') }}</textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" name="amount"
                               class="form-control @error('amount') is-invalid @enderror"
                               value="{{ old('amount', '0.00') }}"
                               placeholder="0.00" required min="0" step="0.01">
                        @error('amount')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Channel</label>
                        <select name="payment_channel" class="form-select @error('payment_channel') is-invalid @enderror">
                            <option value="external" {{ old('payment_channel') === 'external' ? 'selected' : '' }}>External (Online Payment)</option>
                            <option value="internal" {{ old('payment_channel') === 'internal' ? 'selected' : '' }}>Internal (Manual)</option>
                            <option value="both" {{ old('payment_channel', 'both') === 'both' ? 'selected' : '' }}>Both</option>
                        </select>
                        @error('payment_channel')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Purpose</label>
                        <input type="text" name="purpose"
                               class="form-control @error('purpose') is-invalid @enderror"
                               value="{{ old('purpose') }}"
                               list="purpose-suggestions-modal"
                               placeholder="e.g., compulsory_fee, convocation, hostel">
                        <datalist id="purpose-suggestions-modal">
                            @foreach(\App\Models\PaymentType::getPurposes() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </datalist>
                        <small class="text-muted">
                            Pick a known purpose or type any label (e.g. <code>compulsory_fee</code>).
                            Spaces are converted to underscores automatically.
                        </small>
                        @error('purpose')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Audience <span class="text-danger">*</span></label>
                        <select name="audience" class="form-select @error('audience') is-invalid @enderror" required>
                            @foreach(\App\Models\PaymentType::getAudiences() as $value => $label)
                                <option value="{{ $value }}" {{ old('audience', 'both') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted">
                            <strong>Applicant only</strong> hides this type from the public online-payment selector and from any student-facing page.
                            <strong>Student only</strong> hides it from applicants.
                        </small>
                        @error('audience')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority"
                               class="form-control @error('priority') is-invalid @enderror"
                               value="{{ old('priority', 1) }}" min="1">
                        <small class="text-muted">Lower number = shown first</small>
                        @error('priority')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="create_active" {{ old('is_active', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_active">Active</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="requires_payment" class="form-check-input" id="create_payment" {{ old('requires_payment', 1) ? 'checked' : '' }}>
                            <label class="form-check-label" for="create_payment">Requires Payment</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </div>
    </div>
</div>
@if($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.getElementById('createModal');
        if (el && window.bootstrap) { new bootstrap.Modal(el).show(); }
    });
</script>
@endif
@endsection
