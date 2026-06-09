@extends('layouts.app')

@section('title', 'Add Executive - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Add Executive</h4>
        <a href="{{ route('admin.executives.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.executives.store') }}">
                @csrf
                @include('partials.executive-form', ['executive' => null, 'defaultPassword' => $defaultPassword])
                <button type="submit" class="btn btn-primary">Create Executive</button>
            </form>
        </div>
    </div>
@endsection
