<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Bank Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $bank?->name) }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="account_no" class="form-label">Bank Account Number <span class="text-danger">*</span></label>
        <input type="text" name="account_no" id="account_no"
               class="form-control @error('account_no') is-invalid @enderror"
               value="{{ old('account_no', $bank?->account_no) }}" required
               @if(is_null($bank)) oninput="autoFillUsername(this.value)" @endif>
        @error('account_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="branch_name" class="form-label">Bank Branch Name <span class="text-danger">*</span></label>
        <input type="text" name="branch_name" id="branch_name"
               class="form-control @error('branch_name') is-invalid @enderror"
               value="{{ old('branch_name', $bank?->branch_name) }}" required>
        @error('branch_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="account_type" class="form-label">Account Type <span class="text-danger">*</span></label>
        <select name="account_type" id="account_type"
                class="form-select @error('account_type') is-invalid @enderror" required>
            <option value="">— Select —</option>
            @foreach($accountTypeOptions as $value => $label)
                <option value="{{ $value }}"
                    {{ old('account_type', $bank?->account_type) === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('account_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" id="username"
               class="form-control @error('username') is-invalid @enderror"
               value="{{ old('username', $bank?->username) }}" required>
        @if(is_null($bank))
            <div class="form-text">Auto-filled from last 5 digits of account number. Change if needed.</div>
        @endif
        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Phone <span class="text-muted">(optional)</span></label>
        <input type="text" name="phone" id="phone" maxlength="10"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $bank?->phone) }}">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-muted">(optional)</span></label>
        <input type="email" name="email" id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $bank?->email) }}">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    @if(is_null($bank))
        <div class="col-md-6">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input type="text" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   value="{{ old('password', $defaultPassword) }}" required>
            <div class="form-text">A random password is pre-filled. You may change it before saving.</div>
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    @endif
</div>

@if(is_null($bank))
@push('scripts')
<script>
function autoFillUsername(accountNo) {
    const trimmed = accountNo.replace(/\s/g, '');
    if (trimmed.length >= 5) {
        document.getElementById('username').value = trimmed.slice(-5);
    }
}
</script>
@endpush
@endif
