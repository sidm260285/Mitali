@extends('layouts.app')

@section('title', 'Trainer Documents - Mitali SP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">Documents — {{ $trainer->name }}</h4>
            <p class="text-muted mb-0 small">Upload and manage trainer documents. Files are appended per type; delete manually to remove.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.trainers.show', $trainer) }}" class="btn btn-outline-secondary">View Profile</a>
            <a href="{{ route('admin.trainers.index') }}" class="btn btn-outline-secondary">Back to List</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header">
            <h6 class="mb-0">Upload Document</h6>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.trainers.documents.store', $trainer) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                @csrf
                <div class="col-md-4">
                    <label for="type" class="form-label">Document Type <span class="text-danger">*</span></label>
                    <select name="type" id="type" class="form-select @error('type') is-invalid @enderror" required>
                        <option value="">Select type</option>
                        @foreach(\App\Models\TrainerDocument::typeOptions() as $value => $label)
                            <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-5">
                    <label for="file" class="form-label">File <span class="text-danger">*</span></label>
                    <input type="file" name="file" id="file"
                           class="form-control @error('file') is-invalid @enderror" required
                           accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpeg,image/png,image/webp,application/pdf">
                    @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">
                        JPG, PNG, WEBP, or PDF. Max {{ config('trainer.max_file_size_mb') }} MB.
                        @if($hasProfileImage)
                            Profile image already exists — delete it before uploading a new one.
                        @endif
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-form btn-form-primary w-100">
                        <i class="bi bi-upload"></i> Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    @foreach($documentsGrouped as $type => $documents)
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ \App\Models\TrainerDocument::typeOptions()[$type] }}</h6>
                <span class="badge bg-light text-dark">{{ $documents->count() }} file(s)</span>
            </div>
            <div class="card-body p-0">
                @if($documents->isEmpty())
                    <div class="p-4 text-muted text-center">No documents uploaded yet.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>File Name</th>
                                    <th>Uploaded</th>
                                    <th>Size</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($documents as $document)
                                    <tr>
                                        <td>
                                            <i class="bi {{ $document->isPdf() ? 'bi-file-earmark-pdf text-danger' : 'bi-file-earmark-image text-primary' }} me-1"></i>
                                            {{ $document->original_name }}
                                        </td>
                                        <td>{{ $document->created_at->format('d M Y, h:i A') }}</td>
                                        <td>{{ number_format($document->file_size / 1024, 1) }} KB</td>
                                        <td class="text-end">
                                            <div class="table-actions">
                                                <a href="{{ route('admin.trainers.documents.show', [$trainer, $document]) }}"
                                                   class="btn btn-action btn-action-info" target="_blank">
                                                    <i class="bi bi-eye"></i> View
                                                </a>
                                                <form method="POST"
                                                      action="{{ route('admin.trainers.documents.destroy', [$trainer, $document]) }}"
                                                      onsubmit="return confirm('Delete this document?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-action btn-action-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endforeach
@endsection
