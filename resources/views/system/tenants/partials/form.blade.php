<div class="row g-3">
    <div class="col-12 col-md-6">
        <label for="tenant-name" class="form-label">{{ __('Tenant Name') }}</label>
        <input id="tenant-name" type="text" name="name" class="form-control"
            value="{{ old('name', $tenant->name ?? '') }}" placeholder="Demo Garage" required>
    </div>

    <div class="col-12 col-md-6">
        <label for="tenant-status" class="form-label">{{ __('Status') }}</label>
        <select id="tenant-status" name="status" class="form-select" required>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('status', $tenant->status?->value ?? 'active') === $status->value)>
                    {{ ucfirst($status->value) }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="tenant-plan" class="form-label">{{ __('Plan') }}</label>
        <select id="tenant-plan" name="plan_id" class="form-select">
            <option value="">{{ __('No Plan') }}</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((string) old('plan_id', $tenant->plan_id ?? '') === (string) $plan->id)>
                    {{ $plan->name }} ({{ $plan->code }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12 col-md-6">
        <label for="tenant-owner-name" class="form-label">{{ __('Owner Name') }}</label>
        <input id="tenant-owner-name" type="text" name="owner_name" class="form-control"
            value="{{ old('owner_name', data_get($tenant, 'data.owner_name', '')) }}" placeholder="Owner Full Name"
            @if ($isCreate) required @endif>
    </div>

    <div class="col-12 col-md-6">
        <label for="tenant-owner-email" class="form-label">{{ __('Owner Email') }}</label>
        <input id="tenant-owner-email" type="email" name="owner_email" class="form-control"
            value="{{ old('owner_email', data_get($tenant, 'data.owner_email', '')) }}" placeholder="owner@example.com"
            @if ($isCreate) required @endif>
    </div>

    <div class="col-12 col-md-6">
        <label for="tenant-owner-phone" class="form-label">{{ __('Owner Phone') }}</label>
        <input id="tenant-owner-phone" type="text" name="owner_phone" class="form-control"
            value="{{ old('owner_phone', data_get($tenant, 'data.owner_phone', '')) }}" placeholder="+1-555-0000">
    </div>

    @if ($isCreate)
        <div class="col-12 col-md-6">
            <label for="tenant-owner-password" class="form-label">{{ __('Owner Password') }}</label>
            <input id="tenant-owner-password" type="password" name="owner_password" class="form-control"
                placeholder="********" required>
        </div>

        <div class="col-12 col-md-6">
            <label for="tenant-owner-password-confirmation"
                class="form-label">{{ __('Confirm Owner Password') }}</label>
            <input id="tenant-owner-password-confirmation" type="password" name="owner_password_confirmation"
                class="form-control" placeholder="********" required>
        </div>
    @endif
</div>
