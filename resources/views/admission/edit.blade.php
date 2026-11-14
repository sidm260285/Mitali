@extends('layouts.app')

@section('title', 'Edit Admission - ' . $admission->full_name)

@section('content')
@php $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive'; @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Edit Admission: {{ $admission->full_name }}</h4>
    <button type="button" class="btn btn-form btn-form-primary" data-bs-toggle="modal" data-bs-target="#upgradeModal">
        <i class="bi bi-arrow-up-circle"></i> Upgrade
    </button>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white"><h5 class="mb-0">Session</h5></div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Session</label>
                <input type="text" class="form-control" value="{{ $admission->session->name }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Session Start Date</label>
                <input type="text" class="form-control" value="{{ $admission->session_start_date?->format('d M Y') }}" readonly>
            </div>
            <div class="col-md-4">
                <label class="form-label">Session End Date</label>
                <input type="text" class="form-control" value="{{ $admission->session_end_date?->format('d M Y') }}" readonly>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route($prefix . '.admission.update', $admission) }}" id="editAdmissionForm" data-slots="{{ $admission->membership->no_of_slot }}">
    @csrf
    @method('PUT')

    {{-- Personal Details --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Personal Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                    @if($isAdmin)
                        <input type="text" name="full_name" id="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name', $admission->full_name) }}" required>
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @else
                        <input type="text" class="form-control" value="{{ $admission->full_name }}" readonly disabled>
                        <div class="form-text">Only admin can edit the name.</div>
                    @endif
                </div>
                <div class="col-md-2">
                    <label for="physically_disabled" class="form-label">Physically Disabled? <span class="text-danger">*</span></label>
                    <select name="physically_disabled" id="physically_disabled" class="form-select @error('physically_disabled') is-invalid @enderror" required>
                        <option value="">-- Select --</option>
                        <option value="1" @selected((string) old('physically_disabled', $admission->physically_disabled ? '1' : '0') === '1')>Yes</option>
                        <option value="0" @selected((string) old('physically_disabled', $admission->physically_disabled ? '1' : '0') === '0')>No</option>
                    </select>
                    @error('physically_disabled')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                    <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
                        @foreach(\App\Models\Admission::genders() as $g)
                            <option value="{{ $g }}" @selected(old('gender', $admission->gender) === $g)>{{ ucfirst($g) }}</option>
                        @endforeach
                    </select>
                    @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                    <div class="col-md-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="text" class="form-control" value="{{ $admission->date_of_birth->format('d M Y') }}" readonly>
                    </div>
                <div class="col-md-4">
                    <label for="mobile_no" class="form-label">Mobile No. <span class="text-danger">*</span></label>
                    <input type="text" name="mobile_no" id="mobile_no" class="form-control @error('mobile_no') is-invalid @enderror"
                           value="{{ old('mobile_no', $admission->mobile_no) }}" required>
                    @error('mobile_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="guardian_name" class="form-label">Guardian Name <span class="text-danger">*</span></label>
                    <input type="text" name="guardian_name" id="guardian_name" class="form-control @error('guardian_name') is-invalid @enderror"
                           value="{{ old('guardian_name', $admission->guardian_name) }}" required>
                    @error('guardian_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="relation" class="form-label">Relation <span class="text-danger">*</span></label>
                    <select name="relation" id="relation" class="form-select @error('relation') is-invalid @enderror" required>
                        @foreach(\App\Models\Admission::relations() as $r)
                            <option value="{{ $r }}" @selected(old('relation', $admission->relation) === $r)>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                    @error('relation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="emergency_contact_no" class="form-label">Emergency Contact No. <span class="text-danger">*</span></label>
                    <input type="text" name="emergency_contact_no" id="emergency_contact_no" class="form-control @error('emergency_contact_no') is-invalid @enderror"
                           value="{{ old('emergency_contact_no', $admission->emergency_contact_no) }}" required>
                    @error('emergency_contact_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="police_station" class="form-label">Police Station <span class="text-danger">*</span></label>
                    <input type="text" name="police_station" id="police_station" class="form-control @error('police_station') is-invalid @enderror"
                           value="{{ old('police_station', $admission->police_station) }}" required>
                    @error('police_station')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="pin_code" class="form-label">Pin Code <span class="text-danger">*</span></label>
                    <input type="text" name="pin_code" id="pin_code" class="form-control @error('pin_code') is-invalid @enderror"
                           value="{{ old('pin_code', $admission->pin_code) }}" required>
                    @error('pin_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="address" class="form-label">Full Address <span class="text-danger">*</span></label>
                    <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" required>{{ old('address', $admission->address) }}</textarea>
                    @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Swimming Details --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Swimming Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="rfid_code" class="form-label">RFID Code <span class="text-danger">*</span></label>
                    <input type="text" name="rfid_code" id="rfid_code" class="form-control @error('rfid_code') is-invalid @enderror"
                           value="{{ old('rfid_code', $admission->rfid_code) }}" required>
                    @error('rfid_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label">Membership Card</label>
                    @php $slotLabel = $admission->membership->no_of_slot === -1 ? 'Any Slot' : $admission->membership->no_of_slot . ' Slot(s)'; @endphp
                    <input type="text" class="form-control" value="{{ $admission->membership->card_name }} ({{ $slotLabel }})" readonly>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Extended Session</label>
                    <input type="text" class="form-control" value="{{ $admission->is_extended_membership ? 'Yes' : 'No' }}" readonly>
                </div>
                <div class="col-12">
                    <label class="form-label">Select Batch(es) @unless($admission->is_wildcard)<span class="text-danger">*</span>@endunless</label>
                    <div id="batchSelection" class="row g-2">
                        @php $selectedBatchIds = array_map('intval', old('batch_ids', $admission->slots->pluck('session_batch_id')->all())); @endphp
                        @foreach($session->batches as $b)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input batch-check" type="checkbox" name="batch_ids[]"
                                           value="{{ $b->id }}" id="batch_{{ $b->id }}"
                                           @checked(in_array((int) $b->id, $selectedBatchIds, true))>
                                    <label class="form-check-label" for="batch_{{ $b->id }}">
                                        {{ \App\Support\TimeHelper::format12Hour((string) $b->start_time) }} – {{ \App\Support\TimeHelper::format12Hour((string) $b->end_time) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div id="batchHint" class="text-muted small mt-1"></div>
                    @error('batch_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Payment Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Total Amount (₹)</label>
                    <input type="text" class="form-control" value="{{ number_format((float) $admission->amount, 2) }}" readonly>
                    @include('admission.partials.fee-breakdown')
                </div>
                <div class="col-md-4">
                    <label class="form-label">Payment Mode</label>
                    <input type="text" class="form-control" value="{{ ucfirst($admission->payment_mode) }}" readonly>
                </div>
                @if($admission->payment_mode === 'bank')
                    <div class="col-md-4">
                        <label class="form-label">Bank</label>
                        <input type="text" class="form-control" value="{{ $admission->cashTransaction->user->name ?? '—' }}" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Ref Transaction ID</label>
                        <input type="text" class="form-control" value="{{ $admission->cashTransaction->transaction_id ?? '—' }}" readonly>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-form btn-form-primary">
            <i class="bi bi-check-lg"></i> Update Admission
        </button>
        <a href="{{ route($prefix . '.admission.show', $admission) }}" class="btn btn-form btn-form-secondary">
            Cancel
        </a>
    </div>
</form>

@include('admission.partials.upgrade-modal')
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const batchContainer = document.getElementById('batchSelection');
    const batchHint = document.getElementById('batchHint');
    const form = document.getElementById('editAdmissionForm');
    const requiredSlots = parseInt(form.dataset.slots, 10);

    function updateBatchConstraints() {
        const checkboxes = batchContainer.querySelectorAll('.batch-check');

        if (requiredSlots === -1) {
            checkboxes.forEach(cb => { cb.checked = true; cb.disabled = true; });
            batchHint.textContent = 'All batches are automatically selected for this membership.';
            return;
        }

        checkboxes.forEach(cb => { cb.disabled = false; });
        const checkedCount = batchContainer.querySelectorAll('.batch-check:checked').length;
        if (checkedCount >= requiredSlots) {
            checkboxes.forEach(cb => { if (!cb.checked) cb.disabled = true; });
        }
        batchHint.textContent = `Select exactly ${requiredSlots} batch(es).`;
    }

    batchContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('batch-check')) updateBatchConstraints();
    });

    form.addEventListener('submit', function (e) {
        if (requiredSlots === -1) return;

        const checkedCount = batchContainer.querySelectorAll('.batch-check:checked').length;
        if (checkedCount !== requiredSlots) {
            e.preventDefault();
            alert(`Please select exactly ${requiredSlots} batch(es). You have selected ${checkedCount}.`);
        }
    });

    updateBatchConstraints();
});
</script>
@endpush
