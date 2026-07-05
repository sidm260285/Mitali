@extends('layouts.app')

@section('title', 'Admission List')

@section('content')
<h4 class="mb-4">Admission List</h4>

@php $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive'; @endphp

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route($prefix . '.admission.list') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Session</label>
                <select name="session_id" class="form-select" onchange="this.form.submit()">
                    @foreach($sessions as $s)
                        <option value="{{ $s->id }}" @selected($selectedSessionId == $s->id)>
                            {{ $s->name }} ({{ ucfirst($s->status) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Name, Mobile, RFID"
                       value="{{ request('search') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All</option>
                    <option value="active" @selected(request('status') === 'active')>Active</option>
                    <option value="blocked" @selected(request('status') === 'blocked')>Blocked</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Documents</label>
                <select name="doc_status" class="form-select">
                    <option value="">All</option>
                    <option value="complete" @selected(request('doc_status') === 'complete')>Complete</option>
                    <option value="incomplete" @selected(request('doc_status') === 'incomplete')>Incomplete</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-form btn-form-primary w-100">
                    <i class="bi bi-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

@if($admissions instanceof \Illuminate\Pagination\LengthAwarePaginator && $admissions->count() > 0)
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Membership</th>
                    <th>Member Details</th>
                    <th>Emergency Details</th>
                    <th>Allowed Slot</th>
                    <th>Doc Status</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admissions as $index => $admission)
                    <tr>
                        <td>{{ $admissions->firstItem() + $index }}</td>
                        <td>{{ $admission->full_name }}</td>
                        <td>{{ $admission->membership->card_name ?? '—' }}</td>
                        <td class="small">
                            <i class="bi bi-phone"></i> {{ $admission->mobile_no }}<br>
                            <i class="bi bi-person"></i> {{ ucfirst($admission->gender) }}<br>
                            <i class="bi bi-calendar3"></i> {{ $admission->calculatedAge() }} yrs
                        </td>
                        <td class="small">
                            <i class="bi bi-telephone"></i> {{ $admission->emergency_contact_no }}<br>
                            <i class="bi bi-people"></i> {{ $admission->guardian_name }} ({{ ucfirst($admission->relation) }})
                        </td>
                        <td class="small">
                            @foreach($admission->slots as $slot)
                                {{ \App\Support\TimeHelper::format12Hour((string) $slot->batch->start_time) }}–{{ \App\Support\TimeHelper::format12Hour((string) $slot->batch->end_time) }}<br>
                            @endforeach
                        </td>
                        <td>
                            @if($admission->is_document_complete)
                                <span class="badge bg-success">Complete</span>
                            @else
                                <span class="badge bg-warning text-dark">Incomplete</span>
                            @endif
                        </td>
                        <td>
                            @if($admission->isActive())
                                <span class="badge bg-success">Active</span>
                            @elseif($admission->isBlocked())
                                <span class="badge bg-danger">Blocked</span>
                            @else
                                <span class="badge bg-secondary">{{ $admission->statusLabel() }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="{{ route($prefix . '.admission.show', $admission) }}" class="btn btn-action btn-action-info" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route($prefix . '.admission.edit', $admission) }}" class="btn btn-action btn-action-neutral" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" class="btn btn-action btn-action-neutral" disabled title="Show Attendance (Coming soon)">
                                    <i class="bi bi-calendar-check"></i>
                                </button>
                                <button type="button" class="btn btn-action btn-action-neutral" disabled title="Deregister (Coming soon)">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                                @if($admission->isActive())
                                    <form method="POST" action="{{ route($prefix . '.admission.block', $admission) }}" class="d-inline"
                                          onsubmit="return confirm('Block {{ $admission->full_name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-action btn-action-danger" title="Block">
                                            <i class="bi bi-slash-circle"></i>
                                        </button>
                                    </form>
                                @elseif($admission->isBlocked())
                                    <form method="POST" action="{{ route($prefix . '.admission.unblock', $admission) }}" class="d-inline"
                                          onsubmit="return confirm('Unblock {{ $admission->full_name }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-action btn-action-success" title="Unblock">
                                            <i class="bi bi-check-circle"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $admissions->links() }}
    </div>
@else
    <div class="alert alert-info">No admissions found for this session.</div>
@endif
@endsection
