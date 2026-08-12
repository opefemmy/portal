@extends('layouts.app')

@section('title', 'Dean Dashboard')

@section('content')
<div class="page-header">
    <h4>Dean Dashboard</h4>
</div>

@include('widgets.render', ['widgets' => $widgets])
@endsection