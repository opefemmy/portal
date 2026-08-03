@extends('layouts.app')

@section('title', 'Admission Letter Template')

@section('content')
<div class="page-header">
    <h4>Admission Letter Template</h4>
    <p class="text-muted">Edit the body of the admission letter sent to admitted applicants.</p>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-signature me-2"></i>Letter Template Body</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('registrar.admission.uploadTemplate.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label for="template_body" class="form-label">Template Body</label>
                        <textarea name="template_body" id="template_body" class="form-control" rows="18" style="font-family: Georgia, 'Times New Roman', serif; line-height: 1.6;">@if(isset($template)){{ $template }}@elseOn behalf of the @{{ institution_name }}, I am pleased to inform you that you have been offered provisional admission into the @{{ programme }} programme for the @{{ session }} academic session.

You are required to present this letter together with your original credentials to the Admissions Office on or before the resumption date to complete your registration formalities.

Please note that this admission is subject to the verification of all credentials submitted during your application. Any discrepancy found may lead to the withdrawal of this offer.

We congratulate you on this achievement and wish you a successful academic career at @{{ institution_short }}.@endif</textarea>
                        <small class="text-muted">You can use plain text or HTML. The template is saved verbatim.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Template
                    </button>

                    <a href="{{ route('registrar.admission.settings') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Settings
                    </a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Available Placeholders</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">These tokens are replaced with the applicant's details when a letter is generated:</p>
                <ul class="mb-0">
                    <li><code>{{ '{student_name}' }}</code> &mdash; applicant's full name</li>
                    <li><code>{{ '{matric_number}' }}</code> &mdash; matric number</li>
                    <li><code>{{ '{department}' }}</code> &mdash; department name</li>
                    <li><code>{{ '{programme}' }}</code> &mdash; programme name</li>
                    <li><code>{{ '{session}' }}</code> &mdash; academic session</li>
                    <li><code>{{ '{level}' }}</code> &mdash; level of entry</li>
                    <li><code>{{ '{institution_name}' }}</code> &mdash; institution name</li>
                    <li><code>{{ '{institution_short}' }}</code> &mdash; short name</li>
                </ul>
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-lightbulb me-2"></i>
                    Placeholders are optional &mdash; if you don't include them, the letter renders as plain prose.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
