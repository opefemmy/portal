@extends('layouts.app')

@section('title', 'Database Repair')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-server me-2"></i>Database Repair</h4>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Database Tables ({{ count($tables) }})</h5>
    </div>
    <div class="card-body">
        <table class="table table-sm datatable">
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Columns</th>
                    <th>Size (MB)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tables as $table)
                <tr>
                    <td>{{ $table['name'] }}</td>
                    <td>{{ count($table['columns']) }}</td>
                    <td>{{ $table['size'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection