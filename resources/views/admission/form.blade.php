@extends('layouts.app')

@section('title', 'New Admission - ' . ucfirst($sessionType) . ' Session')

@section('content')
<h4 class="mb-4">New Admission ({{ ucfirst($sessionType) }} Session)</h4>

@if($sessions->isEmpty())
    <div class="alert alert-warning">No {{ $sessionType }} session available.</div>
@else
    <form method="POST" action="{{ route((auth()->user()->isAdmin() ? 'admin' : 'executive') . '.admission.store') }}" id="admissionForm">
        @csrf

        {{-- Session Selection --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Session</h5></div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="session_id" class="form-label">Select Session <span class="text-danger">*</span></label>
                    <select name="session_id" id="session_id" class="form-select @error('session_id') is-invalid @enderror" required>
                        @foreach($sessions as $session)
                            <option value="{{ $session->id }}" @selected(old('session_id', $selectedSession?->id) == $session->id)>
                                {{ $session->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('session_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- Personal Details --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Personal Details</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="full_name" class="form-label">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="full_name" id="full_name" class="form-control @error('full_name') is-invalid @enderror"
                               value="{{ old('full_name') }}" required>
                        @error('full_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
                            <option value="">-- Select --</option>
                            @foreach(\App\Models\Admission::genders() as $g)
                                <option value="{{ $g }}" @selected(old('gender') === $g)>{{ ucfirst($g) }}</option>
                            @endforeach
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label for="date_of_birth" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control @error('date_of_birth') is-invalid @enderror"
                               value="{{ old('date_of_birth') }}" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="mobile_no" class="form-label">Mobile No. <span class="text-danger">*</span></label>
                        <input type="text" name="mobile_no" id="mobile_no" class="form-control @error('mobile_no') is-invalid @enderror"
                               value="{{ old('mobile_no') }}" required>
                        @error('mobile_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="guardian_name" class="form-label">Guardian Name <span class="text-danger">*</span></label>
                        <input type="text" name="guardian_name" id="guardian_name" class="form-control @error('guardian_name') is-invalid @enderror"
                               value="{{ old('guardian_name') }}" required>
                        @error('guardian_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="relation" class="form-label">Relation <span class="text-danger">*</span></label>
                        <select name="relation" id="relation" class="form-select @error('relation') is-invalid @enderror" required>
                            <option value="">-- Select --</option>
                            @foreach(\App\Models\Admission::relations() as $r)
                                <option value="{{ $r }}" @selected(old('relation') === $r)>{{ ucfirst($r) }}</option>
                            @endforeach
                        </select>
                        @error('relation')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="emergency_contact_no" class="form-label">Emergency Contact No. <span class="text-danger">*</span></label>
                        <input type="text" name="emergency_contact_no" id="emergency_contact_no" class="form-control @error('emergency_contact_no') is-invalid @enderror"
                               value="{{ old('emergency_contact_no') }}" required>
                        @error('emergency_contact_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="police_station" class="form-label">Police Station <span class="text-danger">*</span></label>
                        <input type="text" name="police_station" id="police_station" class="form-control @error('police_station') is-invalid @enderror"
                               value="{{ old('police_station') }}" required>
                        @error('police_station')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="pin_code" class="form-label">Pin Code <span class="text-danger">*</span></label>
                        <input type="text" name="pin_code" id="pin_code" class="form-control @error('pin_code') is-invalid @enderror"
                               value="{{ old('pin_code') }}" required>
                        @error('pin_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="address" class="form-label">Full Address <span class="text-danger">*</span></label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" required>{{ old('address') }}</textarea>
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
                        <label for="session_membership_id" class="form-label">Membership Card <span class="text-danger">*</span></label>
                        <select name="session_membership_id" id="session_membership_id" class="form-select @error('session_membership_id') is-invalid @enderror" required>
                            <option value="">-- Select Membership --</option>
                        </select>
                        @error('session_membership_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="rfid_code" class="form-label">RFID Code <span class="text-danger">*</span></label>
                        <input type="text" name="rfid_code" id="rfid_code" class="form-control @error('rfid_code') is-invalid @enderror"
                               value="{{ old('rfid_code') }}" required>
                        @error('rfid_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label class="form-label">Select Batch(es) <span class="text-danger">*</span></label>
                        <div id="batchSelection" class="row g-2">
                            <p class="text-muted small">Select a session and membership first.</p>
                        </div>
                        <div id="batchHint" class="text-muted small mt-1"></div>
                        @error('batch_ids')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Payment Details --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Payment Details</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="amount_display" class="form-label">Total Amount (₹)</label>
                        <input type="text" id="amount_display" class="form-control" readonly value="0.00">
                    </div>
                    <div class="col-md-4">
                        <label for="payment_mode" class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror" required>
                            <option value="cash" @selected(old('payment_mode', 'cash') === 'cash')>Cash</option>
                            <option value="bank" @selected(old('payment_mode') === 'bank')>Bank</option>
                        </select>
                        @error('payment_mode')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4" id="bankFields" style="display:none;">
                        <label for="bank_id" class="form-label">Select Bank <span class="text-danger">*</span></label>
                        <select name="bank_id" id="bank_id" class="form-select @error('bank_id') is-invalid @enderror">
                            <option value="">-- Select Bank --</option>
                            @foreach($banks as $bank)
                                <option value="{{ $bank->id }}" @selected(old('bank_id') == $bank->id)>{{ $bank->name }}</option>
                            @endforeach
                        </select>
                        @error('bank_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4" id="transactionIdField" style="display:none;">
                        <label for="transaction_id" class="form-label">Ref Transaction ID <span class="text-danger">*</span></label>
                        <input type="text" name="transaction_id" id="transaction_id" class="form-control @error('transaction_id') is-invalid @enderror"
                               value="{{ old('transaction_id') }}">
                        @error('transaction_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-form btn-form-primary">
                <i class="bi bi-check-lg"></i> Submit Admission
            </button>
        </div>
    </form>
@endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const sessionSelect = document.getElementById('session_id');
    const membershipSelect = document.getElementById('session_membership_id');
    const batchContainer = document.getElementById('batchSelection');
    const batchHint = document.getElementById('batchHint');
    const dobInput = document.getElementById('date_of_birth');
    const amountDisplay = document.getElementById('amount_display');
    const paymentMode = document.getElementById('payment_mode');
    const bankFields = document.getElementById('bankFields');
    const transactionIdField = document.getElementById('transactionIdField');
    const form = document.getElementById('admissionForm');

    const sessionDataUrlBase = "{{ route((auth()->user()->isAdmin() ? 'admin' : 'executive') . '.admission.session-data', ['session' => '__ID__']) }}";

    let sessionData = null;

    function loadSessionData() {
        const sessionId = sessionSelect.value;
        if (!sessionId) return;

        const url = sessionDataUrlBase.replace('__ID__', sessionId);
        fetch(url)
            .then(r => r.json())
            .then(data => {
                sessionData = data;
                populateMemberships(data.memberships);
                populateBatches(data.batches);
                recalculateAmount();
            });
    }

    function populateMemberships(memberships) {
        membershipSelect.innerHTML = '<option value="">-- Select Membership --</option>';
        memberships.forEach(m => {
            const slotLabel = m.no_of_slot === -1 ? 'Any Slot' : m.no_of_slot + ' Slot(s)';
            const opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = `${m.card_name} (${slotLabel}, \u20B9${m.membership_cost.toFixed(2)})`;
            opt.dataset.slots = m.no_of_slot;
            if ("{{ old('session_membership_id') }}" == m.id) opt.selected = true;
            membershipSelect.appendChild(opt);
        });
    }

    function populateBatches(batches) {
        batchContainer.innerHTML = '';
        batches.forEach(b => {
            const col = document.createElement('div');
            col.className = 'col-md-4';
            const availText = b.available !== null ? ` (${b.available} seats)` : '';
            col.innerHTML = `
                <div class="form-check">
                    <input class="form-check-input batch-check" type="checkbox" name="batch_ids[]"
                           value="${b.id}" id="batch_${b.id}">
                    <label class="form-check-label" for="batch_${b.id}">
                        ${b.label}${availText}
                    </label>
                </div>
            `;
            batchContainer.appendChild(col);
        });
        updateBatchConstraints();
    }

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
            checkboxes.forEach(cb => {
                cb.checked = true;
                cb.disabled = true;
            });
            batchHint.textContent = 'All batches are automatically selected for this membership.';
        } else {
            checkboxes.forEach(cb => cb.disabled = false);
            const checkedCount = batchContainer.querySelectorAll('.batch-check:checked').length;

            if (checkedCount >= slots) {
                checkboxes.forEach(cb => {
                    if (!cb.checked) cb.disabled = true;
                });
            }
            batchHint.textContent = `Select exactly ${slots} batch(es).`;
        }
    }

    function resetBatches() {
        const checkboxes = batchContainer.querySelectorAll('.batch-check');
        checkboxes.forEach(cb => {
            cb.checked = false;
            cb.disabled = false;
        });
    }

    function recalculateAmount() {
        if (!sessionData || !membershipSelect.value || !dobInput.value) {
            amountDisplay.value = '0.00';
            return;
        }

        const dob = new Date(dobInput.value);
        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();
        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) age--;
        age = Math.floor(age);

        const ageGroup = sessionData.ages.find(a => age >= a.from_age && age <= a.to_age);
        if (!ageGroup) {
            amountDisplay.value = 'Age not in range';
            return;
        }

        const membership = sessionData.memberships.find(m => m.id == membershipSelect.value);
        if (!membership) {
            amountDisplay.value = '0.00';
            return;
        }

        const total = membership.membership_cost + ageGroup.fee + sessionData.form_fee;
        amountDisplay.value = total.toFixed(2);
    }

    function validateBatchSelection() {
        const slots = getRequiredSlots();
        if (slots === null) {
            alert('Please select a membership card.');
            return false;
        }

        if (slots === -1) return true;

        const checkedCount = batchContainer.querySelectorAll('.batch-check:checked').length;

        if (checkedCount !== slots) {
            alert(`Please select exactly ${slots} batch(es). You have selected ${checkedCount}.`);
            return false;
        }

        return true;
    }

    sessionSelect.addEventListener('change', loadSessionData);

    membershipSelect.addEventListener('change', () => {
        resetBatches();
        updateBatchConstraints();
        recalculateAmount();
    });

    dobInput.addEventListener('change', recalculateAmount);

    batchContainer.addEventListener('change', function (e) {
        if (e.target.classList.contains('batch-check')) {
            updateBatchConstraints();
        }
    });

    paymentMode.addEventListener('change', function () {
        const isBank = this.value === 'bank';
        bankFields.style.display = isBank ? '' : 'none';
        transactionIdField.style.display = isBank ? '' : 'none';
    });

    form.addEventListener('submit', function (e) {
        if (!validateBatchSelection()) {
            e.preventDefault();
        }
    });

    if (paymentMode.value === 'bank') {
        bankFields.style.display = '';
        transactionIdField.style.display = '';
    }

    loadSessionData();
});
</script>
@endpush
