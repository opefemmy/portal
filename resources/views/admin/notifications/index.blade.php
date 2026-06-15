@extends('layouts.app')

@section('title', 'Notification Settings')

@section('content')
<div class="page-header">
    <h4>Notification Settings</h4>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.notifications.update') }}">
            @csrf

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Scrolling Message (Marquee)</h5>
                <div class="mb-3">
                    <label for="scrolling_message" class="form-label">Scrolling Message for Students</label>
                    <textarea class="form-control @error('scrolling_message') is-invalid @enderror"
                              id="scrolling_message" name="scrolling_message" rows="2"
                              placeholder="Enter message to display as scrolling text on student dashboard">{{ old('scrolling_message', $scrolling_message) }}</textarea>
                    <small class="text-muted">This message will appear as a scrolling marquee on student portal</small>
                    @error('scrolling_message')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Login Notification</h5>
                <div class="mb-3">
                    <label for="login_notification" class="form-label">Message to Show on Login</label>
                    <textarea class="form-control @error('login_notification') is-invalid @enderror"
                              id="login_notification" name="login_notification" rows="3"
                              placeholder="Enter notification to show when student logs in">{{ old('login_notification', $login_notification) }}</textarea>
                    <small class="text-muted">This will be displayed as an alert after successful login</small>
                </div>
            </div>

            <div class="mb-4">
                <h5 class="border-bottom pb-2">Post-Login Popup</h5>
                <div class="mb-3">
                    <label for="post_login_message" class="form-label">Popup Message</label>
                    <textarea class="form-control @error('post_login_message') is-invalid @enderror"
                              id="post_login_message" name="post_login_message" rows="4"
                              placeholder="Enter information to show in popup after login">{{ old('post_login_message', $post_login_message) }}</textarea>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="show_post_login_popup" name="show_post_login_popup" value="1"
                           {{ old('show_post_login_popup', $show_post_login_popup) ? 'checked' : '' }}>
                    <label class="form-check-label" for="show_post_login_popup">Enable Post-Login Popup</label>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Preview Section --}}
<div class="card mt-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Preview</h5>
    </div>
    <div class="card-body">
        <h6>Scrolling Message Preview:</h6>
        @if($scrolling_message)
            <div class="alert alert-info">
                <marquee>{{ $scrolling_message }}</marquee>
            </div>
        @else
            <p class="text-muted">No scrolling message configured</p>
        @endif

        <h6 class="mt-3">Login Notification Preview:</h6>
        @if($login_notification)
            <div class="alert alert-success">
                {{ $login_notification }}
            </div>
        @else
            <p class="text-muted">No login notification configured</p>
        @endif
    </div>
</div>
@endsection