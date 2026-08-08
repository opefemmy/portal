{{-- Thin wrapper that renders the shared receipt body inside the
     minimal print layout so DOMPDF has a clean HTML shell. --}}

@extends('layouts.print')

@section('title', 'Payment Receipt — ' . ($payment->reference ?? $payment->payment_ref ?? ''))

@section('content')
    @include('payments._receipt')
@endsection
