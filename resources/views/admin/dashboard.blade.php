@extends('layouts.app')

@section('title', 'Admin Dashboard - Mitali SP')

@section('content')
    <div class="card shadow-sm border-0">
        <div class="card-body p-5 text-center">
            <h2 class="mb-0">Welcome {{ auth()->user()->name }}</h2>
        </div>
    </div>
@endsection
