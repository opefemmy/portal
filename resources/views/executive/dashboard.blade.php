@extends('layouts.app')

@section('title', 'Executive Dashboard')

@section('content')
<div class="container-fluid">
    <h4 class="mb-4">Executive Dashboard</h4>

    {{-- Stat tiles (students, staff, finance, hospital) + Recent Receipts
         table — all widget rendered (executive audience). --}}
    @include('widgets.render', ['widgets' => $widgets])

    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Students by Department</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th>Students</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topDepartments as $dept)
                            <tr>
                                <td>{{ $dept->name }}</td>
                                <td>{{ $dept->count }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2">No data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection