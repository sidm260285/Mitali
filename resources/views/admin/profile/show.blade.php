@extends('layouts.app')

@section('title', 'Profile - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Profile</h4>
        <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">Edit Profile</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @include('partials.profile-fields', ['user' => $user])
        </div>
    </div>
@endsection
