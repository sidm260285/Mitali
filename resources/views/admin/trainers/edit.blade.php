@extends('layouts.app')

@section('title', 'Edit Trainer - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Trainer</h4>
        <div>
            <a href="{{ route('admin.trainers.show', $trainer) }}" class="btn btn-outline-secondary">View Profile</a>
            <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.trainers.update', $trainer) }}">
                @csrf
                @method('PUT')
                @include('partials.trainer-form', ['trainer' => $trainer])
                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-check-lg"></i> Update Trainer
                    </button>
                    <a href="{{ route('admin.trainers.show', $trainer) }}" class="btn btn-form btn-form-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
