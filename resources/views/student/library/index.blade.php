@extends('layouts.app')

@section('title', 'Library')

@section('content')
@php
$user = auth()->user();
@endphp
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Library</h4>
</div>

@if($libraryFeeRequired && !$student->library_fee_paid)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Library Fee Required</h5>
            <p class="mb-0">You must pay the library fee before you can borrow books.</p>
        </div>
        <div class="text-end">
            <h4>₦{{ number_format($libraryFeeAmount, 2) }}</h4>
            <form method="POST" action="{{ route('student.library.pay-fee') }}">
                @csrf
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-credit-card me-2"></i>Pay Now
                </button>
            </form>
        </div>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@elseif($libraryFeeRequired && $student->library_fee_paid)
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>
    Library fee paid on: {{ \Carbon\Carbon::parse($student->library_fee_paid_at)->format('d M Y') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Search Box -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('student.library.search') }}" class="d-flex gap-2">
            <input type="text" name="q" class="form-control" placeholder="Search by title, author, ISBN, or category..." value="{{ $query ?? '' }}">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
            @if(isset($query))
            <a href="{{ route('student.library') }}" class="btn btn-outline-secondary">Clear</a>
            @endif
        </form>
    </div>
</div>

<div class="row">
    <!-- My Loans -->
    <div class="col-md-4 mb-4">
        <div class="card border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-bookmark me-2"></i>My Borrowed Books</h5>
            </div>
            <div class="card-body">
                @if($myLoans->count() > 0)
                    <div class="list-group">
                        @foreach($myLoans as $loan)
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>{{ $loan->book->title }}</strong>
                                    <br><small class="text-muted">{{ $loan->book->author }}</small>
                                </div>
                                <span class="badge bg-{{ $loan->status == 'overdue' ? 'danger' : 'warning' }}">
                                    {{ ucfirst($loan->status) }}
                                </span>
                            </div>
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i>
                                    Due: {{ \Carbon\Carbon::parse($loan->due_date)->format('d M Y') }}
                                </small>
                                @if($loan->late_fee > 0)
                                <br>
                                <small class="text-danger">
                                    <i class="fas fa-exclamation-circle me-1"></i>
                                    Late Fee: ₦{{ number_format($loan->late_fee, 2) }} ({{ $loan->penalty_days }} days)
                                </small>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No books currently borrowed.</p>
                @endif
            </div>
        </div>

        <!-- Loan History -->
        <div class="card mt-3">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Loan History</h5>
            </div>
            <div class="card-body">
                @if($loanHistory->count() > 0)
                    <div class="list-group" style="max-height: 300px; overflow-y: auto;">
                        @foreach($loanHistory as $loan)
                        <div class="list-group-item py-2">
                            <strong>{{ $loan->book->title }}</strong>
                            <br><small class="text-muted">Returned: {{ \Carbon\Carbon::parse($loan->return_date)->format('d M Y') }}</small>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-0">No loan history.</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Available Books -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Available Books</h5>
            </div>
            <div class="card-body">
                @if($books->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Author</th>
                                <th>Category</th>
                                <th>Year</th>
                                <th>Available</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($books as $book)
                            <tr>
                                <td>
                                    <strong>{{ $book->title }}</strong>
                                    @if($book->isbn)
                                    <br><small class="text-muted">ISBN: {{ $book->isbn }}</small>
                                    @endif
                                    @if($book->max_borrow_days)
                                    <br><small class="text-info">Max borrow: {{ $book->max_borrow_days }} days</small>
                                    @endif
                                </td>
                                <td>{{ $book->author }}</td>
                                <td>
                                    @if($book->category)
                                    <span class="badge bg-info">{{ $book->category }}</span>
                                    @else
                                    -
                                    @endif
                                </td>
                                <td>{{ $book->year }}</td>
                                <td>
                                    <span class="badge bg-{{ $book->available > 0 ? 'success' : 'danger' }}">
                                        {{ $book->available }} / {{ $book->quantity }}
                                    </span>
                                </td>
                                <td>
                                    @if($libraryFeeRequired && !$student->library_fee_paid)
                                    <button class="btn btn-sm btn-secondary" disabled title="Pay library fee first">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                    @elseif($book->available > 0)
                                    <form method="POST" action="{{ route('student.library.borrow', $book->id) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-success"
                                                onclick="return confirm('Borrow this book?')">
                                            <i class="fas fa-book-reader"></i> Borrow
                                        </button>
                                    </form>
                                    @else
                                    <button class="btn btn-sm btn-secondary" disabled>
                                        Unavailable
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    {{ $books->links() }}
                </div>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No books available at the moment.</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection