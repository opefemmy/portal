@extends('layouts.app')

@section('title', 'Version Manager')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-tags me-2"></i>Version Manager</h4>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Versions</h5>
            </div>
            <div class="card-body">
                @if(count($versions) > 0)
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Version</th>
                            <th>Release</th>
                            <th>Status</th>
                            <th>Installed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($versions as $version)
                        <tr>
                            <td><code>{{ $version['version'] }}</code></td>
                            <td>{{ $version['release_name'] ?? 'N/A' }}</td>
                            <td>
                                @if($version['is_current'])
                                <span class="badge bg-success">Current</span>
                                @else
                                <span class="badge bg-secondary">Old</span>
                                @endif
                            </td>
                            <td>{{ $version['installed_at'] }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p class="text-muted">No versions recorded</p>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Register New Version</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.maintenance.version.register') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Version</label>
                        <input type="text" name="version" class="form-control" placeholder="v1.0.0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Release Name</label>
                        <input type="text" name="release_name" class="form-control" placeholder="Feature Release">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection