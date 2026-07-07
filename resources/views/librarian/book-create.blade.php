@extends('layouts.app')

@section('title', 'Add New Book')

@section('content')
<div class="page-header">
    <h4>Add New Book</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('librarian.books.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title *</label>
                        <input type="text" class="form-control @error('title') is-invalid @endif"
                               id="title" name="title" value="{{ old('title') }}" required>
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="author" class="form-label">Author *</label>
                        <input type="text" class="form-control @error('author') is-invalid @endif"
                               id="author" name="author" value="{{ old('author') }}" required>
                        @error('author')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="isbn" class="form-label">ISBN</label>
                        <input type="text" class="form-control @error('isbn') is-invalid @endif"
                               id="isbn" name="isbn" value="{{ old('isbn') }}">
                        @error('isbn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="publisher" class="form-label">Publisher</label>
                        <input type="text" class="form-control @error('publisher') is-invalid @endif"
                               id="publisher" name="publisher" value="{{ old('publisher') }}">
                        @error('publisher')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control @error('year') is-invalid @endif"
                               id="year" name="year" value="{{ old('year') }}" min="1900" max="{{ date('Y') }}">
                        @error('year')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="edition" class="form-label">Edition</label>
                        <input type="text" class="form-control @error('edition') is-invalid @endif"
                               id="edition" name="edition" value="{{ old('edition') }}">
                        @error('edition')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity *</label>
                        <input type="number" class="form-control @error('quantity') is-invalid @endif"
                               id="quantity" name="quantity" value="{{ old('quantity', 1) }}" min="1" required>
                        @error('quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control @error('category') is-invalid @endif"
                               id="category" name="category" value="{{ old('category') }}">
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="shelf_location" class="form-label">Shelf Location</label>
                        <input type="text" class="form-control @error('shelf_location') is-invalid @endif"
                               id="shelf_location" name="shelf_location" value="{{ old('shelf_location') }}">
                        @error('shelf_location')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Book
                </button>
                <a href="{{ route('librarian.books') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection