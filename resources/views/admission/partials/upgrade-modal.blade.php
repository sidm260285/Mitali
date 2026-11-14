@php
    $upgradeErrors = $errors->getBag('admissionUpgrade');
    $reopenUpgrade = $upgradeErrors->any();
    $selectedMembershipId = (int) ($reopenUpgrade ? old('session_membership_id', $admission->session_membership_id) : $admission->session_membership_id);
    $selectedExtended = (string) ($reopenUpgrade
        ? old('is_extended_membership', $admission->is_extended_membership ? '1' : '0')
        : ($admission->is_extended_membership ? '1' : '0'));
    $selectedBatches = array_map(
        'intval',
        $reopenUpgrade ? (array) old('batch_ids', []) : $admission->slots->pluck('session_batch_id')->all()
    );
    $graceDays = (int) config('admission.extended_upgrade_grace_days', 30);
    $currentSlotLabel = $admission->membership->no_of_slot === -1
        ? 'Any Slot'
        : $admission->membership->no_of_slot.' Slot(s)';
@endphp

<div class="modal fade" id="upgradeModal" tabindex="-1" aria-labelledby="upgradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <form method="POST" action="{{ route($prefix.'.admission.upgrade', $admission) }}" id="upgradeForm">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="upgradeModalLabel">Upgrade Admission</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($upgradeErrors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($upgradeErrors->all() as $message)
                                    <li>{{ $message }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-3">
                        <label class="form-label" for="upgrade_membership">Update Membership</label>
                        <div class="form-text mb-1">Current membership: {{ $admission->membership->card_name }} ({{ $currentSlotLabel }})</div>
                        @if($admission->is_wildcard)
                            <input type="hidden" name="session_membership_id" value="{{ $admission->session_membership_id }}">
                            <input type="text" id="upgrade_membership" class="form-control" value="{{ $admission->membership->card_name }} ({{ $currentSlotLabel }})" readonly>
                        @else
                            <select name="session_membership_id" id="upgrade_membership" class="form-select @error('session_membership_id', 'admissionUpgrade') is-invalid @enderror">
                                @foreach($upgradeMemberships as $membership)
                                    @php
                                        $slotLabel = $membership->no_of_slot === -1 ? 'Any Slot' : $membership->no_of_slot.' Slot(s)';
                                    @endphp
                                    <option value="{{ $membership->id }}"
                                            data-slots="{{ $membership->no_of_slot }}"
                                            @selected($selectedMembershipId === (int) $membership->id)>
                                        {{ $membership->card_name }} ({{ $slotLabel }}, ₹{{ number_format((float) $membership->membership_cost, 2) }})
                                        @if((int) $membership->id === (int) $admission->session_membership_id)
                                            — Current
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('session_membership_id', 'admissionUpgrade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        @endif
                    </div>

                    <div id="upgradeBatchSection" class="mb-3 d-none">
                        <label class="form-label">Select Batch(es)</label>
                        <div id="upgradeBatches" class="row g-2">
                            @foreach($session->batches as $batch)
                                <div class="col-md-4">
                                    <div class="form-check">
                                        <input class="form-check-input upgrade-batch" type="checkbox" name="batch_ids[]"
                                               value="{{ $batch->id }}" id="upgrade_batch_{{ $batch->id }}"
                                               @checked(in_array((int) $batch->id, $selectedBatches, true))>
                                        <label class="form-check-label" for="upgrade_batch_{{ $batch->id }}">
                                            {{ \App\Support\TimeHelper::format12Hour((string) $batch->start_time) }} – {{ \App\Support\TimeHelper::format12Hour((string) $batch->end_time) }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div id="upgradeBatchHint" class="text-muted small mt-1"></div>
                        @error('batch_ids', 'admissionUpgrade')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="upgrade_extended">Extended Session</label>
                        @if($admission->is_extended_membership)
                            <input type="hidden" name="is_extended_membership" value="1">
                            <input type="text" id="upgrade_extended_display" class="form-control" value="Yes" readonly>
                        @else
                            <select name="is_extended_membership" id="upgrade_extended" class="form-select @error('is_extended_membership', 'admissionUpgrade') is-invalid @enderror">
                                <option value="0" @selected($selectedExtended === '0')>No</option>
                                <option value="1" @selected($selectedExtended === '1')>Yes</option>
                            </select>
                            @error('is_extended_membership', 'admissionUpgrade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">
                                Within {{ $graceDays }} days of admission the extra charge is ₹{{ number_format((float) ($admission->sessionAge->extended_fee ?? 0), 2) }}.
                                After that it is ₹{{ number_format((float) ($admission->sessionAge->extended_fee_late ?? 0), 2) }}.
                            </div>
                        @endif
                    </div>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" for="upgrade_amount">Amount to Pay (₹)</label>
                            <input type="text" id="upgrade_amount" class="form-control" value="0.00" readonly>
                            <div id="upgradeBreakdown" class="small text-muted mt-2">
                                <div id="upgradeGapRow" hidden>Membership gap: ₹<span id="upgradeGap">0.00</span></div>
                                <div id="upgradeExtendedRow" hidden><span id="upgradeExtendedLabel">Extended fee</span>: ₹<span id="upgradeExtendedFee">0.00</span></div>
                                <div id="upgradeQuoteError" class="text-danger"></div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="upgrade_payment_mode">Payment Mode</label>
                            <select name="payment_mode" id="upgrade_payment_mode" class="form-select @error('payment_mode', 'admissionUpgrade') is-invalid @enderror">
                                <option value="cash" @selected(($reopenUpgrade ? old('payment_mode', 'cash') : 'cash') === 'cash')>Cash</option>
                                <option value="bank" @selected($reopenUpgrade && old('payment_mode') === 'bank')>Bank</option>
                            </select>
                            @error('payment_mode', 'admissionUpgrade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="upgradeBankFields" @if(!($reopenUpgrade && old('payment_mode') === 'bank')) style="display:none;" @endif>
                            <label class="form-label" for="upgrade_bank_id">Select Bank</label>
                            <select name="bank_id" id="upgrade_bank_id" class="form-select @error('bank_id', 'admissionUpgrade') is-invalid @enderror">
                                <option value="">-- Select Bank --</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}" @selected((string) old('bank_id') === (string) $bank->id)>{{ $bank->name }}</option>
                                @endforeach
                            </select>
                            @error('bank_id', 'admissionUpgrade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4" id="upgradeTransactionField" @if(!($reopenUpgrade && old('payment_mode') === 'bank')) style="display:none;" @endif>
                            <label class="form-label" for="upgrade_transaction_id">Ref Transaction ID</label>
                            <input type="text" name="transaction_id" id="upgrade_transaction_id" class="form-control @error('transaction_id', 'admissionUpgrade') is-invalid @enderror"
                                   value="{{ $reopenUpgrade ? old('transaction_id') : '' }}">
                            @error('transaction_id', 'admissionUpgrade')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-form btn-form-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="upgradeSubmit" class="btn btn-form btn-form-primary" disabled>Upgrade</button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('upgradeForm');
    const membershipSelect = document.getElementById('upgrade_membership');
    const extendedSelect = document.getElementById('upgrade_extended');
    const batchSection = document.getElementById('upgradeBatchSection');
    const batchHint = document.getElementById('upgradeBatchHint');
    const amountInput = document.getElementById('upgrade_amount');
    const upgradeSubmit = document.getElementById('upgradeSubmit');
    const paymentMode = document.getElementById('upgrade_payment_mode');
    const bankFields = document.getElementById('upgradeBankFields');
    const transactionField = document.getElementById('upgradeTransactionField');
    const gapRow = document.getElementById('upgradeGapRow');
    const gapText = document.getElementById('upgradeGap');
    const extendedRow = document.getElementById('upgradeExtendedRow');
    const extendedLabel = document.getElementById('upgradeExtendedLabel');
    const extendedFeeText = document.getElementById('upgradeExtendedFee');
    const quoteError = document.getElementById('upgradeQuoteError');

    const currentMembershipId = @json((string) $admission->session_membership_id);
    const currentBatchIds = @json($admission->slots->pluck('session_batch_id')->map(fn ($id) => (int) $id)->values());
    const quoteUrl = @json(route($prefix.'.admission.upgrade-quote', $admission));
    const reopenUpgrade = @json($reopenUpgrade);
    const membershipIsSelectable = membershipSelect && membershipSelect.tagName === 'SELECT';

    let quoteRequest = 0;
    let hasQuoteError = false;

    function selectedSlots() {
        if (!membershipIsSelectable) {
            return null;
        }

        const selected = membershipSelect.selectedOptions[0];
        if (!selected || selected.dataset.slots === undefined) {
            return null;
        }

        return parseInt(selected.dataset.slots, 10);
    }

    function membershipChanged() {
        if (!membershipIsSelectable) {
            return false;
        }

        return String(membershipSelect.value) !== currentMembershipId;
    }

    function batchBoxes() {
        return [...batchSection.querySelectorAll('.upgrade-batch')];
    }

    function applyBatchLimit() {
        const slots = selectedSlots();
        const boxes = batchBoxes();

        if (!membershipChanged() || slots === null || slots === -1) {
            return;
        }

        const checkedCount = boxes.filter(box => box.checked).length;
        boxes.forEach(box => {
            box.disabled = !box.checked && checkedCount >= slots;
        });
        batchHint.textContent = `Select exactly ${slots} batch(es).`;
    }

    function syncBatchSection(resetChecks) {
        const slots = selectedSlots();
        const boxes = batchBoxes();
        const changed = membershipChanged();

        if (!changed) {
            batchSection.classList.add('d-none');
            boxes.forEach(box => {
                box.checked = false;
                box.disabled = false;
            });
            batchHint.textContent = '';
            return;
        }

        batchSection.classList.remove('d-none');

        if (slots === -1) {
            boxes.forEach(box => {
                box.checked = true;
                box.disabled = true;
            });
            batchHint.textContent = 'All batches are included for this membership, including batches that are full.';
            return;
        }

        boxes.forEach(box => {
            box.disabled = false;
            if (resetChecks) {
                box.checked = currentBatchIds.includes(parseInt(box.value, 10));
            }
        });
        applyBatchLimit();
    }

    function batchesValid() {
        if (!membershipChanged()) {
            return true;
        }

        const slots = selectedSlots();
        if (slots === -1) {
            return true;
        }

        return batchBoxes().filter(box => box.checked).length === slots;
    }

    function refreshButton() {
        const amount = Number(amountInput.value);
        upgradeSubmit.disabled = !(amount > 0 && batchesValid() && !hasQuoteError);
    }

    function fetchQuote() {
        const requestId = ++quoteRequest;
        const membershipId = membershipIsSelectable ? membershipSelect.value : currentMembershipId;
        const extended = extendedSelect ? extendedSelect.value : '1';
        const params = new URLSearchParams({
            session_membership_id: membershipId,
            is_extended_membership: extended,
        });

        fetch(quoteUrl + '?' + params.toString(), { headers: { 'Accept': 'application/json' } })
            .then(response => response.json())
            .then(data => {
                if (requestId !== quoteRequest) {
                    return;
                }

                hasQuoteError = Boolean(data.error);
                amountInput.value = Number(data.amount || 0).toFixed(2);
                gapRow.hidden = !(Number(data.membership_gap) > 0);
                gapText.textContent = Number(data.membership_gap || 0).toFixed(2);
                extendedRow.hidden = !data.extended_changed;
                extendedLabel.textContent = data.extended_label || 'Extended fee';
                extendedFeeText.textContent = Number(data.extended_fee || 0).toFixed(2);
                quoteError.textContent = data.error || '';
                refreshButton();
            })
            .catch(() => {
                if (requestId !== quoteRequest) {
                    return;
                }

                hasQuoteError = true;
                quoteError.textContent = 'The upgrade amount could not be calculated.';
                refreshButton();
            });
    }

    if (membershipIsSelectable) {
        membershipSelect.addEventListener('change', function () {
            syncBatchSection(true);
            fetchQuote();
        });
    }

    if (extendedSelect) {
        extendedSelect.addEventListener('change', fetchQuote);
    }

    batchSection.addEventListener('change', function (event) {
        if (event.target.classList.contains('upgrade-batch')) {
            applyBatchLimit();
            refreshButton();
        }
    });

    paymentMode.addEventListener('change', function () {
        const isBank = this.value === 'bank';
        bankFields.style.display = isBank ? '' : 'none';
        transactionField.style.display = isBank ? '' : 'none';
    });

    form.addEventListener('submit', function (event) {
        if (upgradeSubmit.disabled || !batchesValid()) {
            event.preventDefault();
            return;
        }

        if (paymentMode.value === 'bank') {
            const bankId = document.getElementById('upgrade_bank_id').value;
            const transactionId = document.getElementById('upgrade_transaction_id').value.trim();
            if (!bankId || !transactionId) {
                event.preventDefault();
                alert('Select a bank and enter the reference transaction ID.');
            }
        }
    });

    syncBatchSection(false);
    fetchQuote();

    if (reopenUpgrade) {
        bootstrap.Modal.getOrCreateInstance(document.getElementById('upgradeModal')).show();
    }
});
</script>
@endpush
