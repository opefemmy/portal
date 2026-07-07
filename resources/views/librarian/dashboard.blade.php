@extends('layouts.app')

@section('title', 'Librarian Dashboard')

@section('content')
<div class="page-header">
    <h4>Librarian Dashboard</h4>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card stat-card success">
            <div class="card-body">
                <h6 class="text-muted">Total Books</h6>
                <h2>{{ $totalBooks }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card info">
            <div class="card-body">
                <h6 class="text-muted">Available Books</h6>
                <h2>{{ $availableBooks }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card warning">
            <div class="card-body">
                <h6 class="text-muted">Borrowed Books</h6>
                <h2>{{ $borrowedBooks }}</h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card stat-card danger">
            <div class="card-body">
                <h6 class="text-muted">Overdue Loans</h6>
                <h2>{{ $overdueLoans }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('librarian.books') }}" class="btn btn-primary">
                        <i class="fas fa-book me-2"></i>Manage Books
                    </a>
                    <a href="{{ route('librarian.books.create') }}" class="btn btn-success">
                        <i class="fas fa-plus me-2"></i>Add New Book
                    </a>
                    <a href="{{ route('librarian.loans') }}" class="btn btn-info">
                        <i class="fas fa-exchange-alt me-2"></i>Manage Loans
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-exclamation-circle me-2"></i>Overdue Books</h5>
            </div>
            <div class="card-body">
                @if($overdueLoans > 0)
                    <p class="text-danger">There are {{ $overdueLoans }} overdue book loans that need attention.</p>
                    <a href="{{ route('librarian.loans') }}" class="btn btn-outline-danger">View Overdue</a>
                @else
                    <p class="text-success">No overdue books at the moment.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection