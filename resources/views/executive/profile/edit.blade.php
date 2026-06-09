@extends('layouts.app')

@section('title', 'Edit Profile - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Profile</h4>
        <a href="{{ route('executive.profile.show') }}" class="btn btn-outline-secondary">Back to Profile</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('executive.profile.update') }}">
                @csrf
                @method('PUT')
                @include('partials.profile-form', ['user' => $user, 'editableUsername' => false])
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </form>
        </div>
    </div>
@endsection
