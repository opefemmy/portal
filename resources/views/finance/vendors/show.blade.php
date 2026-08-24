@extends('layouts.app')

@section('title', $vendor->name)

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4><i class="fas fa-truck me-2"></i>{{ $vendor->name }}</h4>
    <a href="{{ route('finance.vendors.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Back</a>
</div>

<div class="card"><div class="card-body">
    <table class="table table-borderless mb-0">
        <tbody>
            <tr><th width="200">Vendor Name</th><td>{{ $vendor->name }}</td></tr>
            <tr><th>Contact Person</th><td>{{ $vendor->contact_person ?? '—' }}</td></tr>
            <tr><th>Email</th><td>{{ $vendor->email ?? '—' }}</td></tr>
            <tr><th>Phone</th><td>{{ $vendor->phone ?? '—' }}</td></tr>
            <tr><th>Address</th><td>{{ $vendor->address ?? '—' }}</td></tr>
            <tr><th>Status</th><td><span class="badge bg-{{ ($vendor->is_active ?? true) ? 'success' : 'secondary' }}">{{ ($vendor->is_active ?? true) ? 'Active' : 'Inactive' }}</span></td></tr>
        </tbody>
    </table>
</div></div>
@endsection
