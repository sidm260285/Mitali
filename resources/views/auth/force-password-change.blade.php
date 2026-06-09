@extends('layouts.guest')

@section('title', 'Change Password - Mitali SP')

@section('content')
    <div class="card auth-card border-0">
        <div class="card-body p-4">
            <h4 class="card-title text-center mb-2">Set New Password</h4>
            <p class="text-muted text-center mb-4">You must change your password before continuing.</p>
            <form method="POST" action="{{ route('password.force-change.update') }}">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label for="password" class="form-label">New Password</label>
                    <input type="password" name="password" id="password"
                           class="form-control @error('password') is-invalid @enderror" required>
                </div>
                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Update Password</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
                @csrf
                <button type="submit" class="btn btn-link">Logout</button>
            </form>
        </div>
    </div>
@endsection
