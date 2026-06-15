@extends('layouts.app')

@section('title', 'Book Loans')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Book Loans</h4>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Issue New Book</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('admin.library.loans.issue') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <select class="form-select" name="book_id" required>
                    <option value="">Select Book</option>
                    @foreach($books as $book)
                        <option value="{{ $book->id }}">{{ $book->title }} ({{ $book->available }} available)</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select class="form-select" name="student_id" required>
                    <option value="">Select Student</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}">{{ $student->matric_number }} - {{ $student->user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" class="form-control" name="due_date" required>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-success w-100">Issue</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>Book</th>
                    <th>Student</th>
                    <th>Issued By</th>
                    <th>Issue Date</th>
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
                    <td>{{ $loan->student->matric_number ?? 'N/A' }}</td>
                    <td>{{ $loan->issuedBy->name ?? 'N/A' }}</td>
                    <td>{{ $loan->issue_date->format('d M Y') }}</td>
                    <td>{{ $loan->due_date->format('d M Y') }}</td>
                    <td>{{ $loan->return_date?->format('d M Y') ?? '-' }}</td>
                    <td>
                        <span class="badge bg-{{ $loan->status === 'returned' ? 'success' : ($loan->isOverdue() ? 'danger' : 'warning') }}">
                            {{ ucfirst($loan->status) }}
                        </span>
                    </td>
                    <td>
                        @if($loan->status === 'issued')
                        <form method="POST" action="{{ route('admin.library.loans.return', $loan) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-success" data-bs-toggle="tooltip" title="Return Book" onclick="return confirm('Mark as returned?')">
                                <i class="fas fa-check"></i>
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4">No loans found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $loans->links() }}
    </div>
</div>
@endsection