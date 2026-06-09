<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label">Name</label>
        <input type="text" name="name" id="name"
               class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $user->name) }}" required>
    </div>

    <div class="col-md-6">
        <label for="username" class="form-label">Username</label>
        @if($editableUsername)
            <input type="text" name="username" id="username"
                   class="form-control @error('username') is-invalid @enderror"
                   value="{{ old('username', $user->username) }}" required>
        @else
            <input type="text" class="form-control" value="{{ $user->username }}" disabled>
        @endif
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Phone</label>
        <input type="text" name="phone" id="phone" maxlength="10"
               class="form-control @error('phone') is-invalid @enderror"
               value="{{ old('phone', $user->phone) }}" required>
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email <span class="text-muted">(optional)</span></label>
        <input type="email" name="email" id="email"
               class="form-control @error('email') is-invalid @enderror"
               value="{{ old('email', $user->email) }}">
    </div>

    <div class="col-12">
        <label for="address" class="form-label">Address <span class="text-muted">(optional)</span></label>
        <textarea name="address" id="address" rows="3"
                  class="form-control @error('address') is-invalid @enderror">{{ old('address', $user->address) }}</textarea>
    </div>
</div>
