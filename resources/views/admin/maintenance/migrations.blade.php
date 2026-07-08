@extends('layouts.app')

@section('title', 'Migration Manager')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-database me-2"></i>Migration Manager</h4>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0 text-dark">Pending Migrations ({{ count($pending) }})</h5>
            </div>
            <div class="card-body">
                @if(count($pending) > 0)
                <ul class="list-group">
                    @foreach($pending as $migration)
                    <li class="list-group-item"><code>{{ $migration['file'] }}</code></li>
                    @endforeach
                </ul>
                @else
                <p class="text-muted">No pending migrations</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Ran Migrations ({{ count($ran) }})</h5>
            </div>
            <div class="card-body">
                <ul class="list-group" style="max-height: 300px; overflow-y: auto;">
                    @foreach($ran as $migration)
                    <li class="list-group-item"><code>{{ $migration }}</code></li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection