@extends('layouts.app')

@section('title', 'Vendors')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-truck me-2"></i>Vendors</h4>
    <a href="{{ route('finance.vendors.create') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>New Vendor</a>
</div>
@if(session('success'))<div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>@endif
<div class="card"><div class="card-body">
    <div class="table-responsive"><table class="table datatable">
        <thead class="table-light"><tr><th>Vendor</th><th>Contact</th><th>Email</th><th>Phone</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
            @forelse($vendors ?? [] as $v)
                <tr>
                    <td><strong>{{ $v->name }}</strong></td>
                    <td>{{ $v->contact_person ?? '—' }}</td>
                    <td>{{ $v->email ?? '—' }}</td>
                    <td>{{ $v->phone ?? '—' }}</td>
                    <td><span class="badge bg-{{ ($v->is_active ?? true) ? 'success' : 'secondary' }}">{{ ($v->is_active ?? true) ? 'Active' : 'Inactive' }}</span></td>
                    <td>
                        <a href="{{ route('finance.vendors.show', $v) }}" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('finance.vendors.edit', $v) }}" class="btn btn-sm btn-outline-info"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('finance.vendors.destroy', $v) }}" class="d-inline" onsubmit="return confirm('Delete this vendor?')">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center py-4 text-muted">No vendors yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="mt-3">{{ ($vendors ?? null)?->appends(request()->query())->links() }}</div>
</div></div>
@endsection
