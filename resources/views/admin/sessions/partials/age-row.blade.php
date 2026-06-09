<div class="repeater-row {{ !empty($age['frozen']) ? 'frozen' : '' }}" data-age-row>
    @if(!empty($age['id']))
        <input type="hidden" name="ages[{{ $index }}][id]" value="{{ $age['id'] }}">
    @endif
    <div class="mb-2">
        <span class="badge bg-primary age-row-label">Age Range {{ $index + 1 }}</span>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-3">
            <label class="form-label">From Age</label>
            <input type="number" name="ages[{{ $index }}][from_age]" class="form-control"
                   value="{{ $age['from_age'] ?? '' }}" min="1" max="150"
                   {{ !empty($age['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label">To Age</label>
            <input type="number" name="ages[{{ $index }}][to_age]" class="form-control"
                   value="{{ $age['to_age'] ?? '' }}" min="1" max="150"
                   {{ !empty($age['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-md-4">
            <label class="form-label">Fee (₹)</label>
            <input type="number" name="ages[{{ $index }}][fee]" class="form-control" step="0.01" min="0"
                   value="{{ $age['fee'] ?? '' }}"
                   {{ !empty($age['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-md-2 text-end">
            @if(empty($age['frozen']))
                <button type="button" class="btn btn-form btn-form-remove remove-age-row">
                    <i class="bi bi-trash3"></i> Remove
                </button>
            @else
                <span class="badge bg-secondary">Locked</span>
            @endif
        </div>
    </div>
</div>
