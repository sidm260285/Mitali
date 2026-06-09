@extends('layouts.app')

@section('title', 'Edit Accounts Head - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Edit Accounts Head</h4>
        <a href="{{ route('admin.account-heads.index') }}" class="btn btn-outline-secondary">Back to List</a>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.account-heads.update', $accountHead) }}">
                @csrf
                @method('PUT')
                @include('admin.account-heads.partials.form', ['accountHead' => $accountHead])
                <button type="submit" class="btn btn-form btn-form-primary mt-3">Update Accounts Head</button>
            </form>
        </div>
    </div>
@endsection
