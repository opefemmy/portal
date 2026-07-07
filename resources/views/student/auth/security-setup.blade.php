@extends('layouts.app')

@section('title', 'Security Setup')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Set Security Question</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Please set a security question. This will be used to reset your password if you forget it.
                </div>

                @if(session('info'))
                <div class="alert alert-warning">
                    {{ session('info') }}
                </div>
                @endif

                <form method="POST" action="{{ route('student.security.setup') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="security_question" class="form-label">Security Question</label>
                        <select name="security_question" id="security_question" class="form-select @error('security_question') is-invalid @endif" required>
                            <option value="">Select a question</option>
                            <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
                            <option value="What is the name of your first pet?">What is the name of your first pet?</option>
                            <option value="What is the name of your primary school?">What is the name of your primary school?</option>
                            <option value="What is your favorite color?">What is your favorite color?</option>
                            <option value="What is your birth city?">What is your birth city?</option>
                            <option value="What is your best friend's name?">What is your best friend's name?</option>
                            <option value="custom">Custom Question</option>
                        </select>
                        @error('security_question')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>

                    <div class="mb-3" id="custom_question_div" style="display: none;">
                        <label for="custom_question" class="form-label">Enter Your Question</label>
                        <input type="text" name="custom_question" id="custom_question" class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="security_answer" class="form-label">Your Answer</label>
                        <input type="text" name="security_answer" id="security_answer"
                            class="form-control @error('security_answer') is-invalid @endif" required>
                        <small class="text-muted">Remember this answer - you will need it to reset your password.</small>
                        @error('security_answer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="confirm_answer" class="form-label">Confirm Answer</label>
                        <input type="text" name="confirm_answer" id="confirm_answer"
                            class="form-control @error('confirm_answer') is-invalid @endif" required>
                        @error('confirm_answer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @endif
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="fas fa-save me-2"></i>Save Security Question
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('security_question').addEventListener('change', function() {
    var customDiv = document.getElementById('custom_question_div');
    if (this.value === 'custom') {
        customDiv.style.display = 'block';
        document.getElementById('custom_question').setAttribute('required', 'required');
    } else {
        customDiv.style.display = 'none';
        document.getElementById('custom_question').removeAttribute('required');
    }
});
</script>
@endpush
@endsection