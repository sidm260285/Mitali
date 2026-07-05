@extends('layouts.app')

@section('title', 'Edit Admission - ' . $admission->full_name)

@section('content')
@php $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive'; @endphp

<h4 class="mb-4">Edit Admission: {{ $admission->full_name }}</h4>

<form method="POST" action="{{ route($prefix . '.admission.update', $admission) }}" id="editAdmissionForm">
    @csrf
    @method('PUT')

    {{-- Personal Details --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white"><h5 class="mb-0">Personal Details</h5></div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
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
                    <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                    <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                           value="{{ old('date_of_birth', $admission->date_of_birth->format('Y-m-d')) }}" required>
                    @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

                @if($admission->hasAttendance())
                    <div class="col-12">
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i> Membership and batch cannot be changed because this member has attendance records.
                        </div>
                        <div class="mt-2">
                            <strong>Current Membership:</strong> {{ $admission->membership->card_name }}<br>
                            <strong>Current Slots:</strong>
                            @foreach($admission->slots as $slot)
                                {{ \App\Support\TimeHelper::format12Hour((string) $slot->batch->start_time) }} – {{ \App\Support\TimeHelper::format12Hour((string) $slot->batch->end_time) }}@if(!$loop->last), @endif
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="col-md-6">
                        <label for="session_membership_id" class="form-label">Membership Card <span class="text-danger">*</span></label>
                        <select name="session_membership_id" id="session_membership_id" class="form-select @error('session_membership_id') is-invalid @enderror" required>
                            @foreach($session->memberships as $m)
                                @php $slotLabel = $m->no_of_slot === -1 ? 'Any Slot' : $m->no_of_slot . ' Slot(s)'; @endphp
                                <option value="{{ $m->id }}" data-slots="{{ $m->no_of_slot }}"
                                    @selected(old('session_membership_id', $admission->session_membership_id) == $m->id)>
                                    {{ $m->card_name }} ({{ $slotLabel }})
                                </option>
                            @endforeach
                        </select>
                        @error('session_membership_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Select Batch(es) <span class="text-danger">*</span></label>
                        <div id="batchSelection" class="row g-2">
                            @php $selectedBatchIds = old('batch_ids', $admission->slots->pluck('session_batch_id')->toArray()); @endphp
                            @foreach($session->batches as $b)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input batch-check" type="checkbox" name="batch_ids[]"
                                               value="{{ $b->id }}" id="batch_{{ $b->id }}"
                                               @checked(in_array($b->id, $selectedBatchIds))>
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
@endsection

@if(!$admission->hasAttendance())
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const membershipSelect = document.getElementById('session_membership_id');
    const batchContainer = document.getElementById('batchSelection');
    const batchHint = document.getElementById('batchHint');
    const form = document.getElementById('editAdmissionForm');

    function getRequiredSlots() {
        const selected = membershipSelect.selectedOptions[0];
        if (!selected || !selected.dataset.slots) return null;
        return parseInt(selected.dataset.slots);
    }

    function updateBatchConstraints() {
        const slots = getRequiredSlots();
        const checkboxes = batchContainer.querySelectorAll('.batch-check');

        if (slots === null) {
            batchHint.textContent = '';
            return;
        }

        if (slots === -1) {
            checkboxes.forEach(cb => { cb.checked = true; cb.disabled = true; });
            batchHint.textContent = 'All batches are automatically selected for this membership.';
        } else {
            checkboxes.forEach(cb => cb.disabled = false);
            const checkedCount = batchContainer.querySelectorAll('.batch-check:checked').length;
            if (checkedCount >= slots) {
                checkboxes.forEach(cb => { if (!cb.checked) cb.disabled = true; });
            }
            batchHint.textContent = `Select exactly ${slots} batch(es).`;
        }
    }

    function resetBatches() {
        const checkboxes = batchContainer.querySelectorAll('.batch-check');
        checkboxes.forEach(cb => { cb.checked = false; cb.disabled = false; });
    }

    membershipSelect.addEventListener('change', () => {
        resetBatches();
        updateBatchConstraints();
    });

    batchContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('batch-check')) updateBatchConstraints();
    });

    form.addEventListener('submit', function (e) {
        const slots = getRequiredSlots();
        if (slots === null || slots === -1) return;

        const checkedCount = batchContainer.querySelectorAll('.batch-check:checked').length;
        if (checkedCount !== slots) {
            e.preventDefault();
            alert(`Please select exactly ${slots} batch(es). You have selected ${checkedCount}.`);
        }
    });

    updateBatchConstraints();
});
</script>
@endpush
@endif
