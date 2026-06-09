<dl class="row mb-0">
    <dt class="col-sm-3">Name</dt>
    <dd class="col-sm-9">{{ $user->name }}</dd>

    <dt class="col-sm-3">Username</dt>
    <dd class="col-sm-9">{{ $user->username }}</dd>

    <dt class="col-sm-3">Phone</dt>
    <dd class="col-sm-9">{{ $user->phone ?? '—' }}</dd>

    <dt class="col-sm-3">Email</dt>
    <dd class="col-sm-9">{{ $user->email ?? '—' }}</dd>

    <dt class="col-sm-3">Address</dt>
    <dd class="col-sm-9">{{ $user->address ?? '—' }}</dd>
</dl>
