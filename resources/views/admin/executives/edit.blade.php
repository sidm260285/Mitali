@extends('layouts.app')

@section('title', 'Edit Executive - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Executive</h4>
        <a href="{{ route('admin.executives.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.executives.update', $executive) }}">
                @csrf
                @method('PUT')
                @include('partials.executive-form', ['executive' => $executive, 'defaultPassword' => null])
                <button type="submit" class="btn btn-primary">Update Executive</button>
            </form>
        </div>
    </div>
@endsection
