@extends('layouts.app')

@section('title', 'Storage')

@section('content')
<div class="page-header">
    <h4><i class="fas fa-folder me-2"></i>Storage Scanner</h4>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Directories</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($directories as $name => $exists)
                        <tr>
                            <td>{{ $name }}</td>
                            <td>
                                <span class="badge bg-{{ $exists ? 'success' : 'danger' }}">
                                    {{ $exists ? 'Exists' : 'Missing' }}
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Storage Usage (MB)</h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Size (MB)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($usage as $name => $size)
                        <tr>
                            <td>{{ $name }}</td>
                            <td>{{ $size }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection