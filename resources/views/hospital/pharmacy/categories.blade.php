@extends('layouts.app')

@section('title', 'Drug Categories')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-tags me-2"></i>Drug Categories</h3>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>#</th><th>Name</th><th>Description</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse($categories ?? collect() as $c)
                        <tr>
                            <td>{{ $c->id }}</td>
                            <td><strong>{{ $c->name }}</strong></td>
                            <td>{{ $c->description ?? '—' }}</td>
                            <td>
                                <span class="badge bg-{{ ($c->is_active ?? true) ? 'success' : 'secondary' }}">
                                    {{ ($c->is_active ?? true) ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection