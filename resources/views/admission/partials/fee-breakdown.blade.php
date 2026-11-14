@if($admission->feeBreakdown && $admission->membership && $admission->sessionAge)
    <div class="small text-muted mt-2">
        <div>Membership ({{ $admission->membership->card_name }}): ₹{{ number_format((float) $admission->feeBreakdown->membership_amount, 2) }}</div>
        <div>Age Fee ({{ $admission->sessionAge->from_age }}–{{ $admission->sessionAge->to_age }} years): ₹{{ number_format((float) $admission->feeBreakdown->age_fee, 2) }}</div>
        <div>Form Fee: ₹{{ number_format((float) $admission->feeBreakdown->form_fee, 2) }}</div>
        @if($admission->is_extended_membership)
            <div>Extended Fee: ₹{{ number_format((float) $admission->feeBreakdown->extended_fee, 2) }}</div>
        @endif
    </div>
@endif
