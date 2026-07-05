@extends('layouts.app')

@section('title', 'Upload Documents - ' . $admission->full_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Documents: {{ $admission->full_name }}</h4>
    <div class="d-flex gap-2">
        @if($admission->is_document_complete)
            <span class="badge bg-success align-self-center">Documents Complete</span>
        @else
            <span class="badge bg-warning text-dark align-self-center">Documents Incomplete</span>
        @endif
        @php $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive'; @endphp
        <a href="{{ route($prefix . '.admission.list', ['session_id' => $admission->session_id]) }}" class="btn btn-action btn-action-neutral">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<p class="text-muted small mb-4">Upload member documents below. You may skip and upload later.</p>

@foreach([
    \App\Models\AdmissionDocument::TYPE_PHOTO => ['label' => 'Member Photo', 'max' => 1, 'hint' => 'Upload 1 photo only'],
    \App\Models\AdmissionDocument::TYPE_ID_PROOF => ['label' => 'Address & ID Proof (Aadhaar / Voter / Passport)', 'max' => 2, 'hint' => 'Upload up to 2 images (front & back)'],
    \App\Models\AdmissionDocument::TYPE_FITNESS => ['label' => 'Fitness Certificate', 'max' => 5, 'hint' => 'Upload up to 5 images'],
] as $type => $meta)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">{{ $meta['label'] }}</h6>
            <span class="text-muted small">{{ $meta['hint'] }}</span>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3 doc-list" data-type="{{ $type }}">
                @foreach($grouped[$type] as $doc)
                    <div class="col-md-3 doc-item" data-id="{{ $doc->id }}">
                        <div class="border rounded p-2 text-center position-relative">
                            <img src="{{ route((auth()->user()->isAdmin() ? 'admin' : 'executive') . '.admission.documents.show', [$admission, $doc]) }}"
                                 class="img-fluid rounded mb-1" style="max-height: 120px; object-fit: cover;">
                            <p class="small text-truncate mb-1">{{ $doc->original_name }}</p>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-doc" data-doc-id="{{ $doc->id }}">
                                <i class="bi bi-trash3"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            @php $currentCount = $grouped[$type]->count(); @endphp
            @if($currentCount < $meta['max'])
                <div class="upload-zone" data-type="{{ $type }}" data-max="{{ $meta['max'] }}" data-current="{{ $currentCount }}">
                    <input type="file" class="form-control file-input" accept="image/jpeg,image/png,image/webp">
                </div>
            @else
                <p class="text-muted small mb-0">Maximum files uploaded.</p>
            @endif
        </div>
    </div>
@endforeach

<div class="form-actions mb-4">
    @php $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive'; @endphp
    <a href="{{ route($prefix . '.admission.current') }}" class="btn btn-form btn-form-secondary">
        <i class="bi bi-arrow-left"></i> Back to Admissions
    </a>
</div>

{{-- Cropper Modal --}}
<div class="modal fade" id="cropperModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop & Rotate Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div style="max-height: 400px; overflow: hidden;">
                    <img id="cropperImage" style="max-width: 100%; display: block;">
                </div>
                <div class="mt-3 d-flex gap-2 justify-content-center">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="rotateLeft">
                        <i class="bi bi-arrow-counterclockwise"></i> Rotate Left
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="rotateRight">
                        <i class="bi bi-arrow-clockwise"></i> Rotate Right
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="cropConfirm">
                    <i class="bi bi-check-lg"></i> Upload
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">
<style>
    .upload-zone { position: relative; }
    .doc-item img { cursor: pointer; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const admissionId = {{ $admission->id }};
    const prefix = '{{ auth()->user()->isAdmin() ? "admin" : "executive" }}';
    const baseUrl = `/${prefix}/admission/${admissionId}/documents`;
    const csrfToken = '{{ csrf_token() }}';

    let cropper = null;
    let currentFile = null;
    let currentType = null;
    let currentInput = null;

    const modalEl = document.getElementById('cropperModal');
    const modal = new bootstrap.Modal(modalEl);
    const cropperImg = document.getElementById('cropperImage');

    document.querySelectorAll('.file-input').forEach(input => {
        input.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;

            const zone = this.closest('.upload-zone');
            currentType = zone.dataset.type;
            currentInput = this;

            const reader = new FileReader();
            reader.onload = function (e) {
                cropperImg.src = e.target.result;
                modal.show();

                modalEl.addEventListener('shown.bs.modal', function initCropper() {
                    if (cropper) cropper.destroy();

                    const aspectRatio = currentType === '{{ \App\Models\AdmissionDocument::TYPE_PHOTO }}' ? 1 : NaN;
                    cropper = new Cropper(cropperImg, {
                        aspectRatio: aspectRatio,
                        viewMode: 1,
                        autoCropArea: 0.9,
                        responsive: true,
                    });
                    modalEl.removeEventListener('shown.bs.modal', initCropper);
                });
            };
            reader.readAsDataURL(file);
        });
    });

    document.getElementById('rotateLeft').addEventListener('click', () => cropper?.rotate(-90));
    document.getElementById('rotateRight').addEventListener('click', () => cropper?.rotate(90));

    document.getElementById('cropConfirm').addEventListener('click', function () {
        if (!cropper) return;

        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Uploading...';

        cropper.getCroppedCanvas({
            maxWidth: currentType === '{{ \App\Models\AdmissionDocument::TYPE_PHOTO }}' ? 800 : 1200,
            maxHeight: currentType === '{{ \App\Models\AdmissionDocument::TYPE_PHOTO }}' ? 800 : 1200,
        }).toBlob(blob => {
            const formData = new FormData();
            formData.append('file', blob, 'cropped.jpg');
            formData.append('type', currentType);
            formData.append('_token', csrfToken);

            fetch(baseUrl, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Upload failed.');
                }
            })
            .catch(() => alert('Upload failed. Please try again.'))
            .finally(() => {
                document.getElementById('cropConfirm').disabled = false;
                document.getElementById('cropConfirm').innerHTML = '<i class="bi bi-check-lg"></i> Upload';
                modal.hide();
                if (cropper) { cropper.destroy(); cropper = null; }
                if (currentInput) currentInput.value = '';
            });
        }, 'image/jpeg', 0.9);
    });

    modalEl.addEventListener('hidden.bs.modal', function () {
        if (cropper) { cropper.destroy(); cropper = null; }
        if (currentInput) currentInput.value = '';
    });

    document.querySelectorAll('.delete-doc').forEach(btn => {
        btn.addEventListener('click', function () {
            if (!confirm('Delete this document?')) return;

            const docId = this.dataset.docId;
            fetch(`${baseUrl}/${docId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) location.reload();
                else alert('Failed to delete.');
            })
            .catch(() => alert('Failed to delete.'));
        });
    });
});
</script>
@endpush
