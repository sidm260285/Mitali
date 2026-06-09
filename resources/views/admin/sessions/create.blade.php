@extends('layouts.app')

@section('title', 'Add Session - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Add Session</h4>
        <a href="{{ route('admin.sessions.index') }}" class="btn btn-form btn-form-secondary">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>

    <form method="POST" action="{{ route('admin.sessions.store') }}" id="sessionForm">
        @csrf
        @include('admin.sessions.partials.form', [
            'session' => null,
            'statusOptions' => $statusOptions,
        ])
        <div class="form-actions">
            <button type="submit" class="btn btn-form btn-form-primary">
                <i class="bi bi-plus-circle"></i> Create Session
            </button>
            <a href="{{ route('admin.sessions.index') }}" class="btn btn-form btn-form-secondary">
                <i class="bi bi-x-circle"></i> Cancel
            </a>
        </div>
    </form>
@endsection
