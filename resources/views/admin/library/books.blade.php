@extends('layouts.app')

@section('title', 'Library Books')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Library Books</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#uploadModal">
            <i class="fas fa-upload me-2"></i>Upload Books
        </button>
        <a href="{{ route('admin.library.books.create') }}" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Add Book
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-8">
                <input type="text" class="form-control" name="search" placeholder="Search by title, author, ISBN..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table datatable">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Available</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($books as $book)
                <tr>
                    <td>{{ $book->isbn ?? 'N/A' }}</td>
                    <td>{{ $book->title }}</td>
                    <td>{{ $book->author }}</td>
                    <td>{{ $book->category ?? 'N/A' }}</td>
                    <td><span class="badge bg-{{ $book->available > 0 ? 'success' : 'danger' }}">{{ $book->available }}</span></td>
                    <td>{{ $book->quantity }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No books found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        {{ $books->links() }}
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Upload Books (CSV/Excel)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.library.books.upload') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" class="form-control" name="file" accept=".csv,.xlsx,.xls" required>
                        <small class="text-muted">Columns: ISBN, Title, Author, Publisher, Year, Category, Quantity, Shelf Location</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Upload</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection