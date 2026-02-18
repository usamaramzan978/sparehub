<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="plan-code" class="form-label">{{ __('Code') }}</label>
        <input id="plan-code" type="text" name="code" class="form-control" value="{{ old('code', $plan->code ?? '') }}"
            placeholder="STARTER" required>
    </div>

    <div class="col-12 col-md-6">
        <label for="plan-name" class="form-label">{{ __('Name') }}</label>
        <input id="plan-name" type="text" name="name" class="form-control" value="{{ old('name', $plan->name ?? '') }}"
            placeholder="Starter" required>
    </div>

    <div class="col-12">
        <label for="plan-description" class="form-label">{{ __('Description') }}</label>
        <textarea id="plan-description" name="description" class="form-control" rows="3" placeholder="Optional plan notes">{{ old('description', $plan->description ?? '') }}</textarea>
    </div>

    <div class="col-12 col-md-4">
        <label for="plan-monthly-price" class="form-label">{{ __('Monthly Price') }}</label>
        <input id="plan-monthly-price" type="number" step="0.01" min="0" name="monthly_price" class="form-control"
            value="{{ old('monthly_price', $plan->monthly_price ?? '0.00') }}" required>
    </div>

    <div class="col-12 col-md-4">
        <label for="plan-annual-price" class="form-label">{{ __('Annual Price') }}</label>
        <input id="plan-annual-price" type="number" step="0.01" min="0" name="annual_price" class="form-control"
            value="{{ old('annual_price', $plan->annual_price ?? '') }}">
    </div>

    <div class="col-12 col-md-4">
        <label for="plan-status" class="form-label">{{ __('Status') }}</label>
        <select id="plan-status" name="status" class="form-select" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $plan->status?->value ?? 'active') === $status->value)>
                    {{ ucfirst($status->value) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="plan-max-users" class="form-label">{{ __('Max Users') }}</label>
        <input id="plan-max-users" type="number" min="1" name="max_users" class="form-control"
            value="{{ old('max_users', $plan->max_users ?? '') }}" placeholder="Optional">
    </div>

    <div class="col-12 col-md-6">
        <label for="plan-max-branches" class="form-label">{{ __('Max Branches') }}</label>
        <input id="plan-max-branches" type="number" min="1" name="max_branches" class="form-control"
            value="{{ old('max_branches', $plan->max_branches ?? '') }}" placeholder="Optional">
    </div>
</div>
