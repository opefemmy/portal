@extends('layouts.app')

@section('title', 'Deleted Records')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Deleted Records Archive</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="row g-3 mb-3">
            <div class="col-md-4">
                <select name="table_name" class="form-select">
                    <option value="">All Tables</option>
                    <option value="users">Users</option>
                    <option value="students">Students</option>
                    <option value="courses">Courses</option>
                    <option value="payments">Payments</option>
                </select>
            </div>
            <div class="col-md-4">
                <input type="date" name="date" class="form-control">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-secondary w-100">Filter</button>
            </div>
        </form>

        <table class="table datatable">
            <thead>
                <tr>
                    <th>Deleted At</th>
                    <th>Deleted By</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Reason</th>
                    <th>IP Address</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                <tr>
                    <td>{{ $record->created_at->format('d M Y H:i') }}</td>
                    <td>{{ $record->user->name ?? 'Unknown' }}</td>
                    <td>{{ $record->table_name }}</td>
                    <td>{{ $record->record_id }}</td>
                    <td>{{ Str::limit($record->deletion_reason, 30) }}</td>
                    <td>{{ $record->ip_address }}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewModal{{ $record->id }}">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>

                <!-- View Modal -->
                <div class="modal fade" id="viewModal{{ $record->id }}" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Deleted Record Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <pre>{{ json_encode($record->record_data, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <tr>
                    <td colspan="7" class="text-center">No deleted records</td>
                </tr>
                @endforelse
            </tbody>
        </table>

        {{ $records->links() }}
    </div>
</div>
@endsection