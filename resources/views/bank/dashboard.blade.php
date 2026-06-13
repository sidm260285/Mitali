@extends('layouts.app')

@section('title', 'Dashboard - Mitali SP')

@section('content')
    <h4 class="mb-3">Bank Dashboard</h4>
    <p class="text-muted">Welcome, {{ auth()->user()->name }}.</p>
@endsection
