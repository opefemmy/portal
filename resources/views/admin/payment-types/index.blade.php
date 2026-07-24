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
                        <td colspan="7" class="text-center py-4">No payment types configured.</td>
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
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Convocation Fee" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" placeholder="e.g., CONVOCATION" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount (₦) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control" placeholder="0.00" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Channel <span class="text-danger">*</span></label>
                        <select name="payment_channel" class="form-select" required>
                            <option value="external">External (Online Payment)</option>
                            <option value="internal">Internal (Manual)</option>
                            <option value="both">Both</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <input type="number" name="priority" class="form-control" placeholder="1" min="1">
                        <small class="text-muted">Lower number = higher priority</small>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="create_active" checked>
                            <label class="form-check-label" for="create_active">Active</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="requires_payment" class="form-check-input" id="create_payment" checked>
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
@endsection
