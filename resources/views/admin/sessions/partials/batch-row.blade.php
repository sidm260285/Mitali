<div class="repeater-row {{ !empty($batch['frozen']) ? 'frozen' : '' }}" data-batch-row>
    @if(!empty($batch['id']))
        <input type="hidden" name="batches[{{ $index }}][id]" value="{{ $batch['id'] }}">
    @endif
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-primary batch-row-label">Batch {{ $index + 1 }}</span>
    </div>
    <div class="row g-3 align-items-end">
        <div class="col-lg-5">
            <label class="form-label">Start Time</label>
            <div class="row g-2">
                <div class="col-4">
                    <select name="batches[{{ $index }}][start_hour]" class="form-select" {{ !empty($batch['frozen']) ? 'disabled' : 'required' }}>
                        @foreach($hours as $hour)
                            <option value="{{ $hour }}" @selected(($batch['start_hour'] ?? '') == $hour)>{{ $hour }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <select name="batches[{{ $index }}][start_minute]" class="form-select" {{ !empty($batch['frozen']) ? 'disabled' : 'required' }}>
                        @foreach($minutes as $minute)
                            <option value="{{ $minute }}" @selected(($batch['start_minute'] ?? '') == $minute)>{{ str_pad($minute, 2, '0', STR_PAD_LEFT) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <select name="batches[{{ $index }}][start_period]" class="form-select" {{ !empty($batch['frozen']) ? 'disabled' : 'required' }}>
                        <option value="AM" @selected(($batch['start_period'] ?? '') === 'AM')>AM</option>
                        <option value="PM" @selected(($batch['start_period'] ?? '') === 'PM')>PM</option>
                    </select>
                </div>
            </div>
            @if(!empty($batch['frozen']))
                <input type="hidden" name="batches[{{ $index }}][start_hour]" value="{{ $batch['start_hour'] }}">
                <input type="hidden" name="batches[{{ $index }}][start_minute]" value="{{ $batch['start_minute'] }}">
                <input type="hidden" name="batches[{{ $index }}][start_period]" value="{{ $batch['start_period'] }}">
            @endif
        </div>
        <div class="col-lg-5">
            <label class="form-label">End Time</label>
            <div class="row g-2">
                <div class="col-4">
                    <select name="batches[{{ $index }}][end_hour]" class="form-select" {{ !empty($batch['frozen']) ? 'disabled' : 'required' }}>
                        @foreach($hours as $hour)
                            <option value="{{ $hour }}" @selected(($batch['end_hour'] ?? '') == $hour)>{{ $hour }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <select name="batches[{{ $index }}][end_minute]" class="form-select" {{ !empty($batch['frozen']) ? 'disabled' : 'required' }}>
                        @foreach($minutes as $minute)
                            <option value="{{ $minute }}" @selected(($batch['end_minute'] ?? '') == $minute)>{{ str_pad($minute, 2, '0', STR_PAD_LEFT) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-4">
                    <select name="batches[{{ $index }}][end_period]" class="form-select" {{ !empty($batch['frozen']) ? 'disabled' : 'required' }}>
                        <option value="AM" @selected(($batch['end_period'] ?? '') === 'AM')>AM</option>
                        <option value="PM" @selected(($batch['end_period'] ?? '') === 'PM')>PM</option>
                    </select>
                </div>
            </div>
            @if(!empty($batch['frozen']))
                <input type="hidden" name="batches[{{ $index }}][end_hour]" value="{{ $batch['end_hour'] }}">
                <input type="hidden" name="batches[{{ $index }}][end_minute]" value="{{ $batch['end_minute'] }}">
                <input type="hidden" name="batches[{{ $index }}][end_period]" value="{{ $batch['end_period'] }}">
                <input type="hidden" name="batches[{{ $index }}][max_size]" value="{{ $batch['max_size'] ?? 0 }}">
            @endif
        </div>
        <div class="col-lg-1">
            <label class="form-label">Buffer (min)</label>
            <input type="number" name="batches[{{ $index }}][buffer_time]" class="form-control" min="0" max="60"
                   value="{{ $batch['buffer_time'] ?? 0 }}"
                   {{ !empty($batch['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-lg-1">
            <label class="form-label">Max Size</label>
            <input type="number" name="batches[{{ $index }}][max_size]" class="form-control" min="0"
                   value="{{ $batch['max_size'] ?? 0 }}"
                   {{ !empty($batch['frozen']) ? 'readonly' : 'required' }}>
        </div>
        <div class="col-12 text-end">
            @if(empty($batch['frozen']))
                <button type="button" class="btn btn-form btn-form-remove remove-batch-row">
                    <i class="bi bi-trash3"></i> Remove
                </button>
            @else
                <span class="badge bg-secondary">Locked</span>
            @endif
        </div>
    </div>
</div>
