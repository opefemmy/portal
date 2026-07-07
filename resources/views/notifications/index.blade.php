@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <h4>Notifications</h4>
    <button onclick="markAllRead()" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-check-double me-1"></i>Mark All as Read
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
            <div class="list-group-item {{ $notification->is_read ? '' : 'bg-light' }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            @switch($notification->type)
                                @case('success')
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    @break
                                @case('error')
                                    <i class="fas fa-exclamation-circle text-danger me-2"></i>
                                    @break
                                @case('warning')
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    @break
                                @case('info')
                                @default
                                    <i class="fas fa-info-circle text-info me-2"></i>
                            @endswitch
                            <strong>{{ $notification->title }}</strong>
                            @if(!$notification->is_read)
                                <span class="badge bg-primary ms-2">New</span>
                            @endif
                        </div>
                        <p class="mb-1 text-muted">{{ $notification->message }}</p>
                        <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                    </div>
                    <div class="ms-3">
                        @if($notification->link && !$notification->is_read)
                        <a href="{{ $notification->link }}" class="btn btn-sm btn-outline-primary" onclick="markAsRead({{ $notification->id }})">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endif
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteNotification({{ $notification->id }})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
            @empty
            <div class="list-group-item text-center py-5">
                <i class="fas fa-bell-slash text-muted mb-3" style="font-size: 3rem;"></i>
                <p class="text-muted mb-0">No notifications</p>
            </div>
            @endforelse
        </div>
    </div>
</div>

@if($notifications->hasPages())
<div class="mt-3">
    {{ $notifications->links() }}
</div>
@endif

@push('scripts')
<script>
function markAsRead(id) {
    fetch(`/notifications/${id}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    });
}

function markAllRead() {
    fetch('/notifications/mark-all-read', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        }
    }).then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function deleteNotification(id) {
    if (confirm('Delete this notification?')) {
        fetch(`/notifications/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }
}
</script>
@endpush

@endsection