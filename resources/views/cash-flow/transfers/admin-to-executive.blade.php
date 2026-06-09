@extends('layouts.app')

@section('title', 'Transfer to Executive - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Transfer to Executive</h4>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.cash-flow.transfer-to-executive.store') }}">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="executive_id" class="form-label">Executive <span class="text-danger">*</span></label>
                        <select name="executive_id" id="executive_id" class="form-select @error('executive_id') is-invalid @enderror" required>
                            <option value="">Select executive</option>
                            @foreach($executives as $executive)
                                <option value="{{ $executive->id }}" @selected(old('executive_id') == $executive->id)>{{ $executive->name }}</option>
                            @endforeach
                        </select>
                        @error('executive_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @include('cash-flow.partials.entry-fields', ['balance' => $balance, 'allowPastDates' => $allowPastDates])

                <div class="form-actions mt-4">
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-arrow-left-right"></i> Transfer
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
