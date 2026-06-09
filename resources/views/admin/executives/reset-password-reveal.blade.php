@extends('layouts.app')

@section('title', 'Password Reset - Mitali SP')

@section('content')
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 border-warning">
                <div class="card-body p-4">
                    <h4 class="text-warning mb-3">One-Time Password View</h4>
                    <p class="text-muted">
                        A new password has been generated for
                        <strong>{{ $executive->name }}</strong> ({{ $executive->username }}).
                        Copy it now — it will not be shown again.
                    </p>
                    <div class="bg-light border rounded p-3 mb-3">
                        <code class="fs-5 user-select-all">{{ $password }}</code>
                    </div>
                    <p class="small text-muted mb-4">
                        The executive must use this password to log in and will be required to set a new password.
                    </p>
                    <a href="{{ route('admin.executives.index') }}" class="btn btn-primary">Back to Executives</a>
                </div>
            </div>
        </div>
    </div>
@endsection
