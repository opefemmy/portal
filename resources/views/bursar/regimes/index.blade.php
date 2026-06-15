@extends('layouts.app')

@section('title', 'Regime Payments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Regime Payment Configuration</h4>
    <a href="{{ route('bursar.regimes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Regime
    </a>
</div>

<div class="alert alert-info">
    <h5><i class="fas fa-info-circle me-2"></i>Payment Rules:</h5>
    <ul class="mb-0">
        <li><strong>Indigene:</strong> Students from the institution's state</li>
        <li><strong>Non-Indigene:</strong> Students from other states/countries</li>
        <li><strong>60% First Installment:</strong> Must be paid before Second Installment</li>
        <li><strong>40% Second Installment:</strong> Can only be paid after First Installment is completed</li>
        <li><strong>Full Payment:</strong> Pay 100% at once</li>
    </ul>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Student Type</th>
                        <th>Installment</th>
                        <th>Percentage</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($regimes as $regime)
                    <tr>
                        <td>{{ $regime->name }}</td>
                        <td>
                            <span class="badge bg-{{ $regime->student_type == 'Indigene' ? 'success' : 'warning' }}">
                                {{ $regime->student_type }}
                            </span>
                        </td>
                        <td>{{ $regime->installment }}</td>
                        <td>{{ $regime->percentage }}%</td>
                        <td>₦{{ number_format($regime->amount, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $regime->is_active ? 'success' : 'secondary' }}">
                                {{ $regime->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('bursar.regimes.edit', $regime) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this regime">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('bursar.regimes.destroy', $regime) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this regime" onclick="return confirm('Delete this regime?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">No regimes configured.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection