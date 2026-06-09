<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ $filterAction }}" class="row g-3 align-items-end">
            @if(! empty($showExecutiveFilter))
                <div class="col-md-3">
                    <label for="executive_id" class="form-label">Executive</label>
                    <select name="executive_id" id="executive_id" class="form-select" required>
                        <option value="">Select executive</option>
                        @foreach($executives as $executive)
                            <option value="{{ $executive->id }}" @selected($selectedExecutiveId == $executive->id)>
                                {{ $executive->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-md-2">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" name="from_date" id="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-md-2">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" name="to_date" id="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-md-3">
                <label for="account_head_id" class="form-label">Accounts Head</label>
                <select name="account_head_id" id="account_head_id" class="form-select">
                    <option value="">All heads</option>
                    @foreach($accountHeads as $head)
                        <option value="{{ $head->id }}" @selected(request('account_head_id') == $head->id)>
                            {{ $head->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label for="transaction_type" class="form-label">Type</label>
                <select name="transaction_type" id="transaction_type" class="form-select">
                    <option value="">All</option>
                    <option value="credit" @selected(request('transaction_type') === 'credit')>Credit</option>
                    <option value="debit" @selected(request('transaction_type') === 'debit')>Debit</option>
                </select>
            </div>
            <div class="col-md-12">
                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                <a href="{{ $filterAction }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>
