@php
    use App\Support\TimeHelper;

    $isOver = $session?->isOver() ?? false;
    $ages = old('ages', $session ? $session->ages->map(fn ($a) => [
        'id' => $a->id,
        'from_age' => $a->from_age,
        'to_age' => $a->to_age,
        'fee' => $a->fee,
        'frozen' => $a->isFrozen(),
    ])->toArray() : [['from_age' => 1, 'to_age' => 150, 'fee' => 3000, 'frozen' => false]]);

    $batches = old('batches', $session ? $session->batches->map(function ($b) {
        $start = TimeHelper::toParts((string) $b->start_time);
        $end = TimeHelper::toParts((string) $b->end_time);
        return [
            'id' => $b->id,
            'start_hour' => $start['hour'],
            'start_minute' => $start['minute'],
            'start_period' => $start['period'],
            'end_hour' => $end['hour'],
            'end_minute' => $end['minute'],
            'end_period' => $end['period'],
            'buffer_time' => $b->buffer_time,
            'max_size' => $b->max_size,
            'frozen' => $b->isFrozen(),
        ];
    })->toArray() : [[
        'start_hour' => 5, 'start_minute' => 20, 'start_period' => 'AM',
        'end_hour' => 6, 'end_minute' => 0, 'end_period' => 'AM',
        'buffer_time' => 10, 'max_size' => 0, 'frozen' => false,
    ]]);

    $memberships = old('memberships', $session ? $session->memberships->map(fn ($m) => [
        'id' => $m->id,
        'card_name' => $m->card_name,
        'no_of_slot' => $m->no_of_slot,
        'membership_cost' => $m->membership_cost,
        'frozen' => $m->isFrozen(),
    ])->toArray() : [['card_name' => '', 'no_of_slot' => 1, 'membership_cost' => 500, 'frozen' => false]]);

    $hours = range(1, 12);
    $minutes = range(0, 59);
    $slotOptions = [1 => '1 Slot', 2 => '2 Slots', 3 => '3 Slots', 4 => '4 Slots', -1 => 'Any'];
@endphp

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white"><h5 class="mb-0">Session Details</h5></div>
    <div class="card-body">
        @unless($isOver)
            <div class="mb-3">
                <label for="name" class="form-label">Session Name</label>
                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror"
                       value="{{ old('name', $session?->name) }}" required>
            </div>
            <div class="mb-3">
                <label for="form_fee" class="form-label">Form Fee (₹)</label>
                <input type="number" name="form_fee" id="form_fee" class="form-control @error('form_fee') is-invalid @enderror"
                       value="{{ old('form_fee', $session?->form_fee ?? 0) }}" min="0" required>
            </div>
        @endunless
        <div>
            <label for="status" class="form-label">Status</label>
            <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach($statusOptions as $status)
                    <option value="{{ $status }}" @selected(old('status', $session?->status ?? 'upcoming') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            @if($isOver)
                <div class="form-text">Only status can be changed for over sessions.</div>
            @endif
        </div>
    </div>
</div>

@unless($isOver)
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Age Ranges & Fees</h5>
            <button type="button" class="btn btn-form btn-form-add" data-add-row="ageRows">
                <i class="bi bi-plus-lg"></i> Add Range
            </button>
        </div>
        <div class="card-body">
            @error('ages')
                <div class="alert alert-danger py-2 small mb-3"><strong>Age Ranges:</strong> {{ $message }}</div>
            @enderror
            <div id="ageRows">
                @foreach($ages as $index => $age)
                    @include('admin.sessions.partials.age-row', compact('index', 'age', 'hours', 'minutes'))
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Batch Timing</h5>
            <button type="button" class="btn btn-form btn-form-add" data-add-row="batchRows">
                <i class="bi bi-plus-lg"></i> Add Batch
            </button>
        </div>
        <div class="card-body">
            @error('batches')
                <div class="alert alert-danger py-2 small mb-3"><strong>Batch Timing:</strong> {{ $message }}</div>
            @enderror
            <div id="batchRows">
                @foreach($batches as $index => $batch)
                    @include('admin.sessions.partials.batch-row', compact('index', 'batch', 'hours', 'minutes'))
                @endforeach
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Membership Cards</h5>
            <button type="button" class="btn btn-form btn-form-add" data-add-row="membershipRows">
                <i class="bi bi-plus-lg"></i> Add Card
            </button>
        </div>
        <div class="card-body">
            @error('memberships')
                <div class="alert alert-danger py-2 small mb-3"><strong>Membership Cards:</strong> {{ $message }}</div>
            @enderror
            <div id="membershipRows">
                @foreach($memberships as $index => $membership)
                    @include('admin.sessions.partials.membership-row', compact('index', 'membership', 'slotOptions'))
                @endforeach
            </div>
        </div>
    </div>
@endunless

@push('styles')
<style>
    .repeater-row {
        background: #f3f7fb;
        border: 1px solid #d5dee8;
        border-radius: .5rem;
        padding: 1rem;
        margin-bottom: .75rem;
    }
    .repeater-row.frozen {
        background: #e9eef4;
        border: 1px dashed #94a3b8;
    }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    function reindex(container, prefix) {
        container.querySelectorAll('.repeater-row').forEach((row, index) => {
            row.querySelectorAll('[name^="' + prefix + '"]').forEach(input => {
                input.name = input.name.replace(new RegExp(prefix.replace('[', '\\[') + '\\d+\\]'), prefix + index + ']');
            });
        });
    }

    function setSelectValue(select, value) {
        select.value = String(value);
        if (select.value !== String(value)) {
            select.selectedIndex = 0;
        }
    }

    function resetBatchRow(row) {
        const defaults = {
            start_hour: 8, start_minute: 0, start_period: 'AM',
            end_hour: 9, end_minute: 0, end_period: 'AM',
            buffer_time: 10, max_size: 0,
        };
        row.querySelectorAll('select, input').forEach(el => {
            el.removeAttribute('readonly');
            el.removeAttribute('disabled');
            const match = el.name.match(/\[(start_hour|start_minute|start_period|end_hour|end_minute|end_period|buffer_time|max_size)\]$/);
            if (!match) return;
            const field = match[1];
            if (el.tagName === 'SELECT') {
                setSelectValue(el, defaults[field]);
            } else if (el.type === 'number') {
                el.value = defaults[field];
            }
        });
    }

    function updateRowLabels(container, selector, prefix) {
        container.querySelectorAll(selector).forEach((label, index) => {
            label.textContent = prefix + ' ' + (index + 1);
        });
    }

    function updateSectionLabels(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        if (containerId === 'ageRows') updateRowLabels(container, '.age-row-label', 'Age Range');
        if (containerId === 'batchRows') updateRowLabels(container, '.batch-row-label', 'Batch');
        if (containerId === 'membershipRows') updateRowLabels(container, '.membership-row-label', 'Membership');
    }

    document.querySelectorAll('[data-add-row]').forEach(button => {
        button.addEventListener('click', () => {
            const container = document.getElementById(button.dataset.addRow);
            const rows = container.querySelectorAll('.repeater-row:not(.frozen)');
            if (!rows.length) return;
            const clone = rows[rows.length - 1].cloneNode(true);
            clone.classList.remove('frozen');
            clone.querySelectorAll('input[type="hidden"]').forEach(el => el.remove());
            const isBatch = button.dataset.addRow === 'batchRows';

            clone.querySelectorAll('input, select').forEach(el => {
                el.removeAttribute('readonly');
                el.removeAttribute('disabled');
                if (isBatch) return;
                if (el.tagName === 'SELECT') {
                    el.selectedIndex = 0;
                } else if (el.type !== 'hidden') {
                    el.value = el.type === 'number'
                        ? (el.name.includes('fee') || el.name.includes('cost') ? '' : 0)
                        : '';
                }
            });

            if (isBatch) {
                resetBatchRow(clone);
            }

            const lockedBadge = clone.querySelector('.badge.bg-secondary');
            if (lockedBadge) lockedBadge.remove();

            if (!clone.querySelector('.remove-age-row, .remove-batch-row, .remove-membership-row')) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-form btn-form-remove ' + (button.dataset.addRow === 'ageRows' ? 'remove-age-row' : button.dataset.addRow === 'batchRows' ? 'remove-batch-row' : 'remove-membership-row');
                btn.innerHTML = '<i class="bi bi-trash3"></i> Remove';
                clone.querySelector('.text-end, .col-12.text-end, .col-md-2.text-end')?.appendChild(btn);
            }
            container.appendChild(clone);
            const prefix = button.dataset.addRow === 'ageRows' ? 'ages[' : button.dataset.addRow === 'batchRows' ? 'batches[' : 'memberships[';
            reindex(container, prefix);
            updateSectionLabels(button.dataset.addRow);
            if (button.dataset.addRow === 'membershipRows') refreshMembershipSlots();
        });
    });

    document.addEventListener('click', e => {
        if (e.target.matches('.remove-age-row, .remove-batch-row, .remove-membership-row')) {
            const row = e.target.closest('.repeater-row');
            const container = row.parentElement;
            if (container.querySelectorAll('.repeater-row').length > 1) {
                row.remove();
                const prefix = container.id === 'ageRows' ? 'ages[' : container.id === 'batchRows' ? 'batches[' : 'memberships[';
                reindex(container, prefix);
                updateSectionLabels(container.id);
                if (container.id === 'membershipRows') refreshMembershipSlots();
            }
        }
    });

    document.addEventListener('change', e => {
        if (e.target.classList.contains('membership-slot')) refreshMembershipSlots();
    });

    function refreshMembershipSlots() {
        const selects = document.querySelectorAll('.membership-slot');
        const used = new Set();
        selects.forEach(s => { if (s.value) used.add(s.value); });
        selects.forEach(s => {
            const current = s.value;
            s.querySelectorAll('option').forEach(o => {
                o.hidden = o.value && o.value !== current && used.has(o.value);
            });
        });
    }

    refreshMembershipSlots();
});
</script>
@endpush
