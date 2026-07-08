@extends('layouts.app')

@section('title', 'Log Viewer')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-file-alt me-2"></i>Log Viewer</h4>
</div>

@if(isset($error))
<div class="alert alert-danger">{{ $error }}</div>
@else
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Laravel Log</h5>
    </div>
    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
        <pre class="bg-dark text-light p-3" style="font-size: 12px;">@foreach($logs as $log){{ $log }}@endforeach</pre>
    </div>
</div>
@endif
@endsection