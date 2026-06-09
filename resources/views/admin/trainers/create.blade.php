@extends('layouts.app')

@section('title', 'Add Trainer - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Add Trainer</h4>
        <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.trainers.store') }}">
                @csrf
                @include('partials.trainer-form', ['trainer' => null])
                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-person-plus"></i> Create Trainer
                    </button>
                    <a href="{{ route('admin.trainers.index') }}" class="btn btn-form btn-form-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
