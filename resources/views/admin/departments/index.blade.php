@extends('layouts.app')

@section('title', 'Departments')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Departments</h4>
    <a href="{{ route('admin.departments.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Department
    </a>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table datatable">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>School</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
                    <tr>
                        <td>{{ $dept->code }}</td>
                        <td>{{ $dept->name }}</td>
                        <td>{{ $dept->school->name ?? 'N/A' }}</td>
                        <td>
                            <a href="{{ route('admin.departments.edit', $dept) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this department">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.departments.destroy', $dept) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this department" onclick="return confirm('Delete this department?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No departments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection