@extends('layouts.app')

@section('title', 'View Executive - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Executive Details</h4>
        <div>
            <a href="{{ route('admin.executives.edit', $executive) }}" class="btn btn-primary">Edit</a>
            <a href="{{ route('admin.executives.index') }}" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            @include('partials.profile-fields', ['user' => $executive])
            <hr>
            <dl class="row mb-0">
                <dt class="col-sm-3">Status</dt>
                <dd class="col-sm-9">
                    @if($executive->is_active)
                        <span class="badge bg-success">Active</span>
                    @else
                        <span class="badge bg-secondary">Inactive</span>
                    @endif
                </dd>
            </dl>
        </div>
    </div>
@endsection
