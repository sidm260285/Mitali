@extends('layouts.app')

@section('title', 'Change Password - Mitali SP')

@section('content')
    <h4 class="mb-4">Change Password</h4>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('executive.password.update') }}">
                @csrf
                @method('PUT')
                @include('partials.password-form')
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>
@endsection
