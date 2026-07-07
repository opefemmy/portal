@extends('layouts.app')

@section('title', 'Book Loans')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Book Loans</h4>
        <p class="text-muted mb-0">Manage book borrowing and returns</p>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueBookModal">
            <i class="fas fa-plus me-2"></i>Issue Book
        </button>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Book</th>
                        <th>User</th>
                        <th>Loan Date</th>
                        <th>Due Date</th>
                        <th>Return Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $loan)
                    <tr>
                        <td>{{ $loan->book->title ?? 'N/A' }}</td>
                        <td>{{ $loan->user->name ?? 'N/A' }}</td>
                        <td>{{ $loan->loan_date->format('d M Y') }}</td>
                        <td>{{ $loan->due_date->format('d M Y') }}</td>
                        <td>{{ $loan->return_date ? $loan->return_date->format('d M Y') : '-' }}</td>
                        <td>
                            @if($loan->status === 'borrowed')
                                @if($loan->due_date < now())
                                    <span class="badge bg-danger">Overdue</span>
                                @else
                                    <span class="badge bg-warning">Borrowed</span>
                                @endif
                            @elseif($loan->status === 'returned')
                                <span class="badge bg-success">Returned</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($loan->status) }}</span>
                            @endif
                        </td>
                        <td>
                            @if($loan->status === 'borrowed')
                            <form method="POST" action="{{ route('librarian.loans.return', $loan) }}" class="d-inline">
                                @csrf
                                @method('POST')
                                <button type="submit" class="btn btn-sm btn-success" title="Mark Returned">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No loans found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $loans->links() }}
    </div>
</div>

<!-- Issue Book Modal -->
<div class="modal fade" id="issueBookModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Issue Book</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('librarian.loans.issue') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="user_id" class="form-label">User *</label>
                        <select class="form-select @error('user_id') is-invalid @endif" id="user_id" name="user_id" required>
                            <option value="">Select User</option>
                            @foreach(\App\Models\User::where('is_active', true)->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="book_id" class="form-label">Book *</label>
                        <select class="form-select @error('book_id') is-invalid @endif" id="book_id" name="book_id" required>
                            <option value="">Select Book</option>
                            @foreach(\App\Models\Book::where('status', 'available')->get() as $book)
                                <option value="{{ $book->id }}">{{ $book->title }} - {{ $book->author }}</option>
                            @endforeach
                        </select>
                        @error('book_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <label for="due_date" class="form-label">Due Date *</label>
                        <input type="date" class="form-control @error('due_date') is-invalid @endif"
                               id="due_date" name="due_date" value="{{ old('due_date', now()->addDays(14)->format('Y-m-d')) }}" required>
                        @error('due_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Issue Book</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection