@extends('layouts.app')

@section('title', 'View Trainer - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">{{ $trainer->name }}</h4>
            <div>
                @if($trainer->is_active)
                    <span class="badge bg-success">Active</span>
                @else
                    <span class="badge bg-secondary">Inactive</span>
                @endif
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            @if($trainer->is_active)
                <a href="{{ route('admin.trainers.edit', $trainer) }}" class="btn btn-primary">Edit Profile</a>
                <a href="{{ route('admin.trainers.documents.index', $trainer) }}" class="btn btn-outline-primary">Manage Documents</a>
            @else
                <form method="POST" action="{{ route('admin.trainers.activate', $trainer) }}">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-success">Reactivate</button>
                </form>
            @endif
            <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Personal Details</h6>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Name</dt>
                        <dd class="col-sm-8">{{ $trainer->name }}</dd>

                        <dt class="col-sm-4">Date of Birth</dt>
                        <dd class="col-sm-8">{{ $trainer->dob->format('d M Y') }} ({{ $trainer->age() }} years)</dd>

                        <dt class="col-sm-4">Gender</dt>
                        <dd class="col-sm-8">{{ $trainer->genderLabel() }}</dd>

                        <dt class="col-sm-4">Mobile</dt>
                        <dd class="col-sm-8">{{ $trainer->mobile }}</dd>

                        <dt class="col-sm-4">Alternative Mobile</dt>
                        <dd class="col-sm-8">{{ $trainer->alternative_mobile ?? '—' }}</dd>

                        <dt class="col-sm-4">Joining Date</dt>
                        <dd class="col-sm-8">{{ $trainer->joining_date->format('d M Y') }}</dd>

                        <dt class="col-sm-4">Monthly Salary</dt>
                        <dd class="col-sm-8">₹{{ number_format($trainer->monthly_salary) }}</dd>

                        <dt class="col-sm-4">Permanent Address</dt>
                        <dd class="col-sm-8">{{ $trainer->permanent_address }}</dd>

                        <dt class="col-sm-4">Current Address</dt>
                        <dd class="col-sm-8">{{ $trainer->current_address }}</dd>
                    </dl>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header">
                    <h6 class="mb-0">Bank Details</h6>
                </div>
                <div class="card-body">
                    @if($trainer->bank_account_number || $trainer->bank_name || $trainer->bank_ifsc)
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Account Holder</dt>
                            <dd class="col-sm-8">{{ $trainer->bank_account_holder_name ?? $trainer->name }}</dd>

                            <dt class="col-sm-4">Account Number</dt>
                            <dd class="col-sm-8">{{ $trainer->bank_account_number ?? '—' }}</dd>

                            <dt class="col-sm-4">Bank Name</dt>
                            <dd class="col-sm-8">{{ $trainer->bank_name ?? '—' }}</dd>

                            <dt class="col-sm-4">IFSC Code</dt>
                            <dd class="col-sm-8">{{ $trainer->bank_ifsc ?? '—' }}</dd>
                        </dl>
                    @else
                        <p class="text-muted mb-0">No bank details provided.</p>
                    @endif
                </div>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Documents</h6>
                    @if($trainer->is_active)
                        <a href="{{ route('admin.trainers.documents.index', $trainer) }}" class="btn btn-sm btn-outline-primary">Manage Documents</a>
                    @endif
                </div>
                <div class="card-body">
                    @php $grouped = $trainer->documentsGroupedByType(); @endphp
                    @foreach($grouped as $type => $documents)
                        @continue($type === \App\Models\TrainerDocument::TYPE_PROFILE_IMAGE)
                        <div class="mb-3">
                            <div class="fw-semibold small text-uppercase text-muted mb-2">
                                {{ \App\Models\TrainerDocument::typeOptions()[$type] }}
                            </div>
                            @if($documents->isEmpty())
                                <p class="text-muted small mb-0">No files uploaded.</p>
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach($documents as $document)
                                        <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="bi {{ $document->isPdf() ? 'bi-file-earmark-pdf' : 'bi-file-earmark-image' }} me-1"></i>
                                                {{ $document->original_name }}
                                            </span>
                                            <a href="{{ route('admin.trainers.documents.show', [$trainer, $document]) }}"
                                               class="btn btn-sm btn-outline-secondary" target="_blank">View</a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm border-0">
                <div class="card-header">
                    <h6 class="mb-0">Profile Image</h6>
                </div>
                <div class="card-body text-center">
                    @if($trainer->profileImage)
                        <img src="{{ route('admin.trainers.documents.show', [$trainer, $trainer->profileImage]) }}"
                             alt="Profile image of {{ $trainer->name }}"
                             class="img-fluid rounded border"
                             style="max-height: 320px; object-fit: contain;">
                        <div class="mt-2 small text-muted">{{ $trainer->profileImage->original_name }}</div>
                    @else
                        <div class="text-muted py-5">
                            <i class="bi bi-person-circle display-4 d-block mb-2"></i>
                            No profile image uploaded.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
