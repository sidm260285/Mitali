@extends('layouts.app')

@section('title', 'Edit Session - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Session</h4>
        <a href="{{ route('admin.sessions.index', ['tab' => $session->status]) }}" class="btn btn-form btn-form-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('admin.sessions.update', $session) }}" id="sessionForm">
        @csrf
        @method('PUT')
        @include('admin.sessions.partials.form', [
            'session' => $session,
            'statusOptions' => $statusOptions,
        ])
        <div class="form-actions">
            <button type="submit" class="btn btn-form btn-form-primary">
                <i class="bi bi-check-circle"></i> Update Session
            </button>
            <a href="{{ route('admin.sessions.index', ['tab' => $session->status]) }}" class="btn btn-form btn-form-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </div>
    </form>
@endsection
