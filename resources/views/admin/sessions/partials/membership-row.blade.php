<div class="repeater-row {{ !empty($membership['frozen']) ? 'frozen' : '' }}" data-membership-row>
    @if(!empty($membership['id']))
        <input type="hidden" name="memberships[{{ $index }}][id]" value="{{ $membership['id'] }}">
    @endif
    <div class="mb-2">
        <span class="badge bg-primary membership-row-label">Membership {{ $index + 1 }}</span>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label">Card Name</label>
            <input type="text" name="memberships[{{ $index }}][card_name]" class="form-control"
                   value="{{ $membership['card_name'] ?? '' }}" maxlength="200"
                   {{ !empty($membership['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-md-3">
            <label class="form-label">No. of Slots</label>
            <select name="memberships[{{ $index }}][no_of_slot]" class="form-select membership-slot"
                    {{ !empty($membership['frozen']) ? 'disabled' : 'required' }}>
                @foreach($slotOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($membership['no_of_slot'] ?? '') == $value)>{{ $label }}</option>
                @endforeach
            </select>
            @if(!empty($membership['frozen']))
                <input type="hidden" name="memberships[{{ $index }}][no_of_slot]" value="{{ $membership['no_of_slot'] }}">
            @endif
        </div>
        <div class="col-md-3">
            <label class="form-label">Membership Cost (₹)</label>
            <input type="number" name="memberships[{{ $index }}][membership_cost]" class="form-control" step="0.01" min="0"
                   value="{{ $membership['membership_cost'] ?? '' }}"
                   {{ !empty($membership['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-md-2 text-end">
            @if(empty($membership['frozen']))
                <button type="button" class="btn btn-form btn-form-remove remove-membership-row">
                    <i class="bi bi-trash3"></i> Remove
                </button>
            @else
                <span class="badge bg-secondary">Locked</span>
            @endif
        </div>
    </div>
</div>
