<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $executive?->name) }}" required>
    </div>

    <div class="col-md-6">
        <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" id="username"
               class="form-control @error('username') is-invalid @enderror"
               value="{{ old('username', $executive?->username) }}" required>
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
        <input type="text" name="phone" id="phone" maxlength="10"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $executive?->phone) }}" required>
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-muted">(optional)</span></label>
        <input type="email" name="email" id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $executive?->email) }}">
    </div>

    <div class="col-12">
        <label for="address" class="form-label">Address <span class="text-muted">(optional)</span></label>
        <textarea name="address" id="address" rows="3"
                  class="form-control @error('address') is-invalid @enderror">{{ old('address', $executive?->address) }}</textarea>
    </div>

    @if(is_null($executive))
        <div class="col-md-6">
            <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
            <input type="text" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   value="{{ old('password', $defaultPassword) }}" required>
            <div class="form-text">A random password is pre-filled. You may change it before saving.</div>
        </div>
    @endif
</div>
