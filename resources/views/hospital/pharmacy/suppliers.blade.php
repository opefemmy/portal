@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="container-fluid">
    <h3 class="mb-3"><i class="fas fa-truck me-2"></i>Suppliers</h3>

    @if($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
    @endif

    <div class="card mb-3">
        <div class="card-header bg-primary text-white"><i class="fas fa-plus me-1"></i> Add Supplier</div>
        <div class="card-body">
            <form method="POST" action="{{ route('hospital.pharmacy.suppliers.store') }}" class="row g-2">
                @csrf
                <div class="col-md-3"><input class="form-control" name="name" placeholder="Name *" required></div>
                <div class="col-md-2"><input class="form-control" name="code" placeholder="Code *" required></div>
                <div class="col-md-2"><input class="form-control" name="phone" placeholder="Phone *" required></div>
                <div class="col-md-2"><input type="email" class="form-control" name="email" placeholder="Email"></div>
                <div class="col-md-2"><input class="form-control" name="address" placeholder="Address"></div>
                <div class="col-md-1"><button class="btn btn-primary w-100"><i class="fas fa-save"></i></button></div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr><th>Name</th><th>Code</th><th>Phone</th><th>Email</th><th>Address</th></tr>
                </thead>
                <tbody>
                    @forelse($suppliers ?? collect() as $s)
                        <tr>
                            <td><strong>{{ $s->name }}</strong></td>
                            <td>{{ $s->code }}</td>
                            <td>{{ $s->phone }}</td>
                            <td>{{ $s->email ?: '—' }}</td>
                            <td>{{ $s->address ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No suppliers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection