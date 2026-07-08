@extends('layouts.app')

@section('title', 'My Complaints')

@section('content')
<div class="page-header">
    <h4 class="mb-0">My Complaints</h4>
    <p class="text-muted mb-0">Submit and track your complaints, suggestions, and inquiries.</p>
</div>

<!-- Success Message -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Submit New Complaint Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Submit New Complaint</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('student.complaints.store') }}">
            @csrf
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="category" class="form-label">Category *</label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="complaint">Complaint</option>
                            <option value="suggestion">Suggestion</option>
                            <option value="inquiry">Inquiry</option>
                        </select>
                        @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject *</label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror"
                               id="subject" name="subject" required>
                        @error('subject')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message *</label>
                <textarea class="form-control @error('message') is-invalid @enderror"
                          id="message" name="message" rows="5" required></textarea>
                @error('message')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane me-2"></i>Submit
            </button>
        </form>
    </div>
</div>

<!-- Complaints List -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>My Previous Complaints</h5>
    </div>
    <div class="card-body">
        @if($complaints->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Category</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Response</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($complaints as $complaint)
                    <tr>
                        <td>{{ $complaint->created_at->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-{{ $complaint->category == 'complaint' ? 'danger' : ($complaint->category == 'suggestion' ? 'info' : 'secondary') }}">
                                {{ ucfirst($complaint->category) }}
                            </span>
                        </td>
                        <td>{{ $complaint->subject }}</td>
                        <td>
                            <span class="badge bg-{{ $complaint->status == 'pending' ? 'warning' : ($complaint->status == 'resolved' ? 'success' : 'primary') }}">
                                {{ ucfirst($complaint->status) }}
                            </span>
                        </td>
                        <td>
                            @if($complaint->response)
                                <button type="button" class="btn btn-sm btn-outline-info" data-bs-toggle="modal" data-bs-target="#responseModal{{ $complaint->id }}">
                                    <i class="fas fa-eye"></i> View
                                </button>

                                <!-- Response Modal -->
                                <div class="modal fade" id="responseModal{{ $complaint->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Response</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p><strong>Your Subject:</strong> {{ $complaint->subject }}</p>
                                                <hr>
                                                <p><strong>Response:</strong></p>
                                                <p>{{ $complaint->response }}</p>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <span class="text-muted">No response yet</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-4">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted">No complaints submitted yet.</p>
        </div>
        @endif
    </div>
</div>
@endsection