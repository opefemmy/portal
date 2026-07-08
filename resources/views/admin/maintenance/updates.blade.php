@extends('layouts.app')

@section('title', 'Update Manager')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-sync me-2"></i>Update Manager</h4>
</div>

<div class="row">
    {{-- Pending Migrations --}}
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Pending Migrations</h5>
                <span class="badge bg-warning">{{ count($pendingMigrations) }} pending</span>
            </div>
            <div class="card-body">
                @if(count($pendingMigrations) > 0)
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Migration File</th>
                            <th>Path</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingMigrations as $migration)
                        <tr>
                            <td><code>{{ $migration['file'] }}</code></td>
                            <td><small class="text-muted">{{ $migration['path'] }}</small></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>

                <form method="POST" action="{{ route('admin.maintenance.migrations.run') }}" class="mt-3">
                    @csrf
                    <button type="submit" class="btn btn-success" onclick="return confirm('Create backup before running migrations?')">
                        <i class="fas fa-play me-2"></i>Run All Migrations
                    </button>
                </form>
                @else
                <div class="text-center py-4">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <p class="text-muted">No pending migrations - system is up to date!</p>
                </div>
                @endif
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-seedling me-2"></i>Run Seeders</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.maintenance.seeders.run') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Select Seeder (optional)</label>
                        <select name="seeder" class="form-select">
                            <option value="">Run All Seeders</option>
                            <option value="DatabaseSeeder">Database Seeder</option>
                            <option value="StatesAndLGAsSeeder">States & LGAs</option>
                            <option value="ERPRolesSeeder">ERP Roles</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-play me-2"></i>Run Seeders
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- Quick Repairs --}}
    <div class="col-md-4">
        <div class="card border-warning">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0"><i class="fas fa-wrench me-2"></i>Quick Repairs</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.maintenance.repairs.run') }}">
                    @csrf
                    <p class="text-muted">This will repair:</p>
                    <ul class="text-muted">
                        <li>Missing tables</li>
                        <li>Missing columns</li>
                        <li>Permissions & roles</li>
                        <li>Grading scales</li>
                        <li>Sessions & semesters</li>
                        <li>System settings</li>
                    </ul>
                    <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Create backup before repairs?')">
                        <i class="fas fa-tools me-2"></i>Run All Repairs
                    </button>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-database me-2"></i>Backups</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <form method="POST" action="{{ route('admin.maintenance.backup.create') }}">
                        @csrf
                        <input type="hidden" name="type" value="database">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="fas fa-database me-2"></i>Backup Database
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.maintenance.backup.create') }}">
                        @csrf
                        <input type="hidden" name="type" value="files">
                        <button type="submit" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-folder me-2"></i>Backup Files
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection