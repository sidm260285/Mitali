@php
    $genders = \App\Models\Trainer::genderOptions();
@endphp

<div class="row g-3">
    <div class="col-12">
        <h6 class="text-muted text-uppercase small fw-semibold mb-0">Personal Details</h6>
    </div>

    <div class="col-md-6">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $trainer?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="dob" class="form-label">Date of Birth <span class="text-danger">*</span></label>
        <input type="date" name="dob" id="dob"
               class="form-control @error('dob') is-invalid @enderror"
               max="{{ now()->subYears(10)->format('Y-m-d') }}"
               value="{{ old('dob', $trainer?->dob?->format('Y-m-d')) }}" required>
        @error('dob')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Trainer must be at least 10 years old.</div>
    </div>

    <div class="col-md-3">
        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
        <select name="gender" id="gender" class="form-select @error('gender') is-invalid @enderror" required>
            <option value="">Select gender</option>
            @foreach($genders as $value => $label)
                <option value="{{ $value }}" @selected(old('gender', $trainer?->gender) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="mobile" class="form-label">Mobile <span class="text-danger">*</span></label>
        <input type="text" name="mobile" id="mobile" maxlength="10" inputmode="numeric"
               class="form-control @error('mobile') is-invalid @enderror"
               value="{{ old('mobile', $trainer?->mobile) }}" required>
        @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="alternative_mobile" class="form-label">Alternative Mobile</label>
        <input type="text" name="alternative_mobile" id="alternative_mobile" maxlength="10" inputmode="numeric"
               class="form-control @error('alternative_mobile') is-invalid @enderror"
               value="{{ old('alternative_mobile', $trainer?->alternative_mobile) }}">
        @error('alternative_mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="joining_date" class="form-label">Joining Date <span class="text-danger">*</span></label>
        <input type="date" name="joining_date" id="joining_date"
               class="form-control @error('joining_date') is-invalid @enderror"
               max="{{ now()->format('Y-m-d') }}"
               value="{{ old('joining_date', $trainer?->joining_date?->format('Y-m-d')) }}" required>
        @error('joining_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="monthly_salary" class="form-label">Monthly Salary (INR) <span class="text-danger">*</span></label>
        <input type="number" name="monthly_salary" id="monthly_salary" min="0" step="1"
               class="form-control @error('monthly_salary') is-invalid @enderror"
               value="{{ old('monthly_salary', $trainer?->monthly_salary) }}" required>
        @error('monthly_salary')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label for="permanent_address" class="form-label">Permanent Address <span class="text-danger">*</span></label>
        <textarea name="permanent_address" id="permanent_address" rows="3"
                  class="form-control @error('permanent_address') is-invalid @enderror" required>{{ old('permanent_address', $trainer?->permanent_address) }}</textarea>
        @error('permanent_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" id="same_as_permanent">
            <label class="form-check-label" for="same_as_permanent">Same as permanent address</label>
        </div>
        <label for="current_address" class="form-label">Current Address <span class="text-danger">*</span></label>
        <textarea name="current_address" id="current_address" rows="3"
                  class="form-control @error('current_address') is-invalid @enderror" required>{{ old('current_address', $trainer?->current_address) }}</textarea>
        @error('current_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mt-2">
        <h6 class="text-muted text-uppercase small fw-semibold mb-0">Bank Details <span class="text-muted fw-normal">(optional)</span></h6>
    </div>

    <div class="col-md-6">
        <label for="bank_account_holder_name" class="form-label">Account Holder Name</label>
        <input type="text" id="bank_account_holder_name" class="form-control"
               value="{{ old('name', $trainer?->name) }}" readonly disabled>
        <div class="form-text">Auto-filled from trainer name.</div>
    </div>

    <div class="col-md-6">
        <label for="bank_account_number" class="form-label">Account Number</label>
        <input type="text" name="bank_account_number" id="bank_account_number"
               class="form-control @error('bank_account_number') is-invalid @enderror"
               value="{{ old('bank_account_number', $trainer?->bank_account_number) }}">
        @error('bank_account_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="bank_name" class="form-label">Bank Name</label>
        <input type="text" name="bank_name" id="bank_name"
               class="form-control @error('bank_name') is-invalid @enderror"
               value="{{ old('bank_name', $trainer?->bank_name) }}">
        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="bank_ifsc" class="form-label">IFSC Code</label>
        <input type="text" name="bank_ifsc" id="bank_ifsc"
               class="form-control @error('bank_ifsc') is-invalid @enderror"
               value="{{ old('bank_ifsc', $trainer?->bank_ifsc) }}">
        @error('bank_ifsc')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const permanent = document.getElementById('permanent_address');
        const current = document.getElementById('current_address');
        const sameCheckbox = document.getElementById('same_as_permanent');
        const nameInput = document.getElementById('name');
        const holderName = document.getElementById('bank_account_holder_name');

        function syncCurrentFromPermanent() {
            if (sameCheckbox.checked) {
                current.value = permanent.value;
                current.readOnly = true;
            } else {
                current.readOnly = false;
            }
        }

        sameCheckbox.addEventListener('change', syncCurrentFromPermanent);
        permanent.addEventListener('input', function () {
            if (sameCheckbox.checked) {
                current.value = permanent.value;
            }
        });

        nameInput.addEventListener('input', function () {
            holderName.value = nameInput.value;
        });

        if (permanent.value && current.value && permanent.value === current.value) {
            sameCheckbox.checked = true;
        }

        syncCurrentFromPermanent();
    })();
</script>
@endpush
