@extends('layouts.app')

@section('title', 'Library Books')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4 class="mb-0">Library Books</h4>
        <p class="text-muted mb-0">Manage library book collection</p>
    </div>
    <div>
        <a href="{{ route('librarian.books.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add New Book
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover datatable">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Author</th>
                        <th>ISBN</th>
                        <th>Publisher</th>
                        <th>Year</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($books as $book)
                    <tr>
                        <td>{{ $book->title }}</td>
                        <td>{{ $book->author }}</td>
                        <td>{{ $book->isbn ?? 'N/A' }}</td>
                        <td>{{ $book->publisher ?? 'N/A' }}</td>
                        <td>{{ $book->year ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $book->status === 'available' ? 'success' : 'warning' }}">
                                {{ ucfirst($book->status) }}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" title="View">
                                <i class="fas fa-eye"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No books found. Add some books to get started.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $books->links() }}
    </div>
</div>
@endsection