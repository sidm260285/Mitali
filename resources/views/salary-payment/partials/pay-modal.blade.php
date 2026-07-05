<div class="modal fade" id="payModal" tabindex="-1" aria-labelledby="payModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.salary-payment.store') }}" id="payForm">
                @csrf
                <input type="hidden" name="payee_type" id="payeeType">
                <input type="hidden" name="payee_id" id="payeeId">
                <input type="hidden" name="salary_month" id="salaryMonth">
                <input type="hidden" name="salary_year" id="salaryYear">

                <div class="modal-header">
                    <h5 class="modal-title" id="payModalLabel">Pay Salary</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <dl class="row mb-3">
                        <dt class="col-sm-5">Payee</dt>
                        <dd class="col-sm-7" id="modalPayeeName"></dd>
                        <dt class="col-sm-5">Salary Amount</dt>
                        <dd class="col-sm-7" id="modalSalaryAmount"></dd>
                        <dt class="col-sm-5">Already Paid</dt>
                        <dd class="col-sm-7" id="modalPaidAmount"></dd>
                        <dt class="col-sm-5">Remaining</dt>
                        <dd class="col-sm-7 fw-bold" id="modalRemaining"></dd>
                    </dl>

                    <div class="mb-3">
                        <label for="payAmount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" name="amount" id="payAmount" step="0.01" min="0.01"
                               class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label for="payMode" class="form-label">Mode <span class="text-danger">*</span></label>
                        <select name="mode" id="payMode" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="bankFields">
                        <div class="mb-3">
                            <label for="payBankId" class="form-label">Bank <span class="text-danger">*</span></label>
                            <select name="bank_id" id="payBankId" class="form-select">
                                <option value="">Select bank</option>
                                @foreach($banks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }} - {{ substr($bank->account_no, -5) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="payTransactionId" class="form-label">Ref Transaction ID <span class="text-danger">*</span></label>
                            <input type="text" name="transaction_id" id="payTransactionId" class="form-control" maxlength="255">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="payNarration" class="form-label">Narration</label>
                        <input type="text" name="narration" id="payNarration" class="form-control" maxlength="400">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-form btn-form-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-form btn-form-primary">
                        <i class="bi bi-wallet2"></i> Pay Salary
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const modal = document.getElementById('payModal');
    const modeSelect = document.getElementById('payMode');
    const bankFields = document.getElementById('bankFields');
    const bankIdSelect = document.getElementById('payBankId');
    const transactionIdInput = document.getElementById('payTransactionId');

    modeSelect.addEventListener('change', function () {
        if (this.value === 'bank') {
            bankFields.classList.remove('d-none');
            bankIdSelect.setAttribute('required', 'required');
            transactionIdInput.setAttribute('required', 'required');
        } else {
            bankFields.classList.add('d-none');
            bankIdSelect.removeAttribute('required');
            transactionIdInput.removeAttribute('required');
        }
    });

    modal.addEventListener('show.bs.modal', function (event) {
        const btn = event.relatedTarget;

        document.getElementById('payeeType').value = btn.dataset.payeeType;
        document.getElementById('payeeId').value = btn.dataset.payeeId;
        document.getElementById('salaryMonth').value = btn.dataset.month;
        document.getElementById('salaryYear').value = btn.dataset.year;
        document.getElementById('modalPayeeName').textContent = btn.dataset.name;
        document.getElementById('modalSalaryAmount').textContent = '₹' + parseFloat(btn.dataset.salary).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modalPaidAmount').textContent = '₹' + parseFloat(btn.dataset.paid).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('modalRemaining').textContent = '₹' + parseFloat(btn.dataset.remaining).toLocaleString('en-IN', {minimumFractionDigits: 2});
        document.getElementById('payAmount').value = btn.dataset.remaining;
        document.getElementById('payAmount').max = btn.dataset.remaining;

        modeSelect.value = 'cash';
        bankFields.classList.add('d-none');
        bankIdSelect.removeAttribute('required');
        bankIdSelect.value = '';
        transactionIdInput.removeAttribute('required');
        transactionIdInput.value = '';
        document.getElementById('payNarration').value = '';
    });
})();
</script>
@endpush
