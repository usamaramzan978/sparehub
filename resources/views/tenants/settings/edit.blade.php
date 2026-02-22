@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [['label' => __('Settings')]];
        $setting = $settings;
    @endphp

    <x-breadcrumb title="{{ __('Settings') }}" :items="$breadcrumbs" />

    <div class="row">
        <div class="col-lg-3 mb-4">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-body">
                    <h6 class="mb-3">{{ __('Settings') }}</h6>
                    <div class="list-group list-group-flush">
                        <a class="list-group-item list-group-item-action" href="#general-settings">
                            <i class="ri-settings-3-line me-2"></i>{{ __('General Settings') }}
                        </a>
                        <a class="list-group-item list-group-item-action" href="#notification-settings">
                            <i class="ri-notification-3-line me-2"></i>{{ __('Notifications') }}
                        </a>
                        <a class="list-group-item list-group-item-action" href="#system-settings">
                            <i class="ri-cpu-line me-2"></i>{{ __('System Settings') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-9 mb-3">
            <form method="POST" action="{{ route('tenant.settings.update') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="card custom-card border-0 shadow-sm h-100" id="general-settings">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('General Settings') }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">{{ __('Basic company profile used across invoices and headers.') }}
                        </p>
                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="company_name">{{ __('Company Name') }}</label>
                                <input type="text" name="company_name" id="company_name"
                                    class="form-control @error('company_name') is-invalid @enderror"
                                    value="{{ old('company_name', $setting?->company_name) }}">
                                @error('company_name')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="logo">{{ __('Logo') }}</label>
                                <input type="file" name="logo" id="logo"
                                    class="form-control @error('logo') is-invalid @enderror" accept="image/*">
                                @error('logo')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                                @if ($setting?->logo_path)
                                    <div class="mt-2">
                                        <img src="{{ asset('storage/' . $setting->logo_path) }}" alt="{{ __('Logo') }}"
                                            class="img-thumbnail" style="max-height: 80px;">
                                    </div>
                                @endif
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="support_email">{{ __('Support Email') }}</label>
                                <input type="email" name="support_email" id="support_email"
                                    class="form-control @error('support_email') is-invalid @enderror"
                                    value="{{ old('support_email', $setting?->support_email) }}">
                                @error('support_email')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="support_phone">{{ __('Support Phone') }}</label>
                                <input type="text" name="support_phone" id="support_phone"
                                    class="form-control @error('support_phone') is-invalid @enderror"
                                    value="{{ old('support_phone', $setting?->support_phone) }}">
                                @error('support_phone')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-card border-0 shadow-sm h-100" id="notification-settings">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Notifications') }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">{{ __('Choose how the system sends operational alerts.') }}</p>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <input type="hidden" name="email_notifications_enabled" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="email_notifications_enabled" name="email_notifications_enabled" value="1"
                                        @checked(old('email_notifications_enabled', $setting?->email_notifications_enabled ?? true))>
                                    <label class="form-check-label"
                                        for="email_notifications_enabled">{{ __('Email Alerts') }}</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card custom-card border-0 shadow-sm h-100" id="system-settings">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('System Settings') }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">{{ __('Security related system controls.') }}</p>
                        <div class="row">
                            <div class="col-md-4 mb-3 d-flex align-items-end">
                                <input type="hidden" name="two_factor_enabled" value="0">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        id="two_factor_enabled" name="two_factor_enabled" value="1"
                                        @checked(old('two_factor_enabled', $setting?->two_factor_enabled ?? false))>
                                    <label class="form-check-label"
                                        for="two_factor_enabled">{{ __('Enable Two-Factor Authentication') }}</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label" for="two_factor_method">{{ __('Two-Factor Method') }}</label>
                                <select name="two_factor_method" id="two_factor_method"
                                    class="form-select @error('two_factor_method') is-invalid @enderror">
                                    <option value="">{{ __('Select a method') }}</option>
                                    <option value="email" @selected(old('two_factor_method', $setting?->two_factor_method?->value) === 'email')>
                                        {{ __('Email') }}
                                    </option>
                                    <option value="authenticator" @selected(old('two_factor_method', $setting?->two_factor_method?->value) === 'authenticator')>
                                        {{ __('Authenticator App') }}
                                    </option>
                                </select>
                                @error('two_factor_method')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <div class="alert alert-info mt-2 mb-0">
                            {{ __('Authenticator enrollment and recovery codes are managed in Profile Security.') }}
                            <a href="{{ route('tenant.profile.security.show') }}"
                                class="fw-semibold text-decoration-underline">{{ __('Open Security Page') }}</a>
                        </div>
                    </div>
                </div>

                <div class="card custom-card border-0 shadow-sm h-100" id="timezone-settings">
                    <div class="card-header">
                        <h6 class="mb-0">{{ __('Timezone') }}</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">{{ __('Choose how the system sets time') }}</p>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="timezone">{{ __('Timezone') }}</label>
                                <select name="timezone" id="timezone"
                                    class="form-select singl-select-2 @error('timezone') is-invalid @enderror">
                                    @foreach ($timezones as $timezone)
                                        <option value="{{ $timezone }}" @selected(old('timezone', $setting?->timezone) === $timezone)>
                                            {{ $timezone }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('timezone')
                                    <span class="invalid-feedback d-block">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
@endpush
