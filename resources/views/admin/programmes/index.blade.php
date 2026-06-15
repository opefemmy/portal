@extends('layouts.app')

@section('title', 'Programmes')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Programmes</h4>
    <a href="{{ route('admin.programmes.create') }}" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Add Programme
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
                        <th>Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($programmes as $prog)
                    <tr>
                        <td>{{ $prog->code }}</td>
                        <td>{{ $prog->name }}</td>
                        <td>{{ $prog->type }}</td>
                        <td>
                            <a href="{{ route('admin.programmes.edit', $prog) }}" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Edit this programme">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.programmes.destroy', $prog) }}" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Delete this programme" onclick="return confirm('Delete this programme?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-4">No programmes found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection