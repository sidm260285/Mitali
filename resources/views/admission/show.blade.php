@extends('layouts.app')

@section('title', 'View Admission - ' . $admission->full_name)

@section('content')
@php $prefix = auth()->user()->isAdmin() ? 'admin' : 'executive'; @endphp

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Admission Details</h4>
    <div class="d-flex gap-2">
        <a href="{{ route($prefix . '.admission.edit', $admission) }}" class="btn btn-action btn-action-neutral">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route($prefix . '.admission.documents', $admission) }}" class="btn btn-action btn-action-info">
            <i class="bi bi-file-earmark-image"></i> Documents
        </a>
        <a href="{{ route($prefix . '.admission.list', ['session_id' => $admission->session_id]) }}" class="btn btn-action btn-action-neutral">
            <i class="bi bi-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<div class="row g-4">
    {{-- Personal Details --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white"><h6 class="mb-0">Personal Details</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">Full Name</th><td>{{ $admission->full_name }}</td></tr>
                    <tr><th class="text-muted">Gender</th><td>{{ ucfirst($admission->gender) }}</td></tr>
                    <tr><th class="text-muted">Date of Birth</th><td>{{ $admission->date_of_birth->format('d M Y') }}</td></tr>
                    <tr><th class="text-muted">Age</th><td>{{ $admission->calculatedAge() }} years</td></tr>
                    <tr><th class="text-muted">Mobile No.</th><td>{{ $admission->mobile_no }}</td></tr>
                    <tr><th class="text-muted">Guardian Name</th><td>{{ $admission->guardian_name }}</td></tr>
                    <tr><th class="text-muted">Relation</th><td>{{ ucfirst($admission->relation) }}</td></tr>
                    <tr><th class="text-muted">Emergency Contact</th><td>{{ $admission->emergency_contact_no }}</td></tr>
                    <tr><th class="text-muted">Address</th><td>{{ $admission->address }}</td></tr>
                    <tr><th class="text-muted">Police Station</th><td>{{ $admission->police_station }}</td></tr>
                    <tr><th class="text-muted">Pin Code</th><td>{{ $admission->pin_code }}</td></tr>
                </table>
            </div>
        </div>
    </div>

    {{-- Swimming & Payment Details --}}
    <div class="col-md-6">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Swimming Details</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">Session</th><td>{{ $admission->session->name }}</td></tr>
                    <tr><th class="text-muted">Membership Card</th><td>{{ $admission->membership->card_name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">RFID Code</th><td><code>{{ $admission->rfid_code }}</code></td></tr>
                    <tr>
                        <th class="text-muted">Allowed Slots</th>
                        <td>
                            @foreach($admission->slots as $slot)
                                {{ \App\Support\TimeHelper::format12Hour((string) $slot->batch->start_time) }} – {{ \App\Support\TimeHelper::format12Hour((string) $slot->batch->end_time) }}<br>
                            @endforeach
                        </td>
                    </tr>
                    <tr><th class="text-muted">Wildcard</th><td>{{ $admission->is_wildcard ? 'Yes' : 'No' }}</td></tr>
                </table>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white"><h6 class="mb-0">Payment & Status</h6></div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr><th class="text-muted" style="width:40%">Amount</th><td><strong>₹{{ number_format((float)$admission->amount, 2) }}</strong></td></tr>
                    <tr><th class="text-muted">Payment Mode</th><td>{{ ucfirst($admission->payment_mode) }}</td></tr>
                    @if($admission->cashTransaction)
                        <tr><th class="text-muted">Transaction ID</th><td>{{ $admission->cashTransaction->transaction_id ?? '—' }}</td></tr>
                    @endif
                    <tr>
                        <th class="text-muted">Status</th>
                        <td>
                            @if($admission->isActive())
                                <span class="badge bg-success">Active</span>
                            @elseif($admission->isBlocked())
                                <span class="badge bg-danger">Blocked</span>
                            @else
                                <span class="badge bg-secondary">{{ $admission->statusLabel() }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th class="text-muted">Attendance Count</th><td>{{ $admission->attendance_count }}</td></tr>
                    <tr>
                        <th class="text-muted">Documents</th>
                        <td>
                            @if($admission->is_document_complete)
                                <span class="badge bg-success">Complete</span>
                            @else
                                <span class="badge bg-warning text-dark">Incomplete</span>
                            @endif
                        </td>
                    </tr>
                    <tr><th class="text-muted">Admitted By</th><td>{{ $admission->admittedByUser->name ?? '—' }}</td></tr>
                    <tr><th class="text-muted">Admitted On</th><td>{{ $admission->created_at->format('d M Y, h:i A') }}</td></tr>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
