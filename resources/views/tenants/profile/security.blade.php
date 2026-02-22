@extends('layouts.app')

@section('content')
    @php
        $breadcrumbs = [
            ['label' => __('Account')],
            ['label' => __('Profile'), 'url' => route('tenant.profile.show')],
            ['label' => __('Security')],
        ];
        $isAuthenticatorMethod = $twoFactorMethod === \App\Enums\TwoFactorMethod::AUTHENTICATOR;
    @endphp

    <x-breadcrumb title="{{ __('Profile Security') }}" :items="$breadcrumbs">
        <x-slot:actions>
            <a href="{{ route('tenant.profile.show') }}" class="btn btn-outline-secondary">{{ __('Back To Profile') }}</a>
        </x-slot:actions>
    </x-breadcrumb>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-12 col-xl-7">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Authenticator Enrollment') }}</h6>
                </div>
                <div class="card-body">
                    @if (!$authenticatorEnabledForTenant)
                        <div class="alert alert-warning mb-0">
                            {{ __('Authenticator is currently disabled at tenant settings level.') }}
                        </div>
                    @elseif (!$isAuthenticatorMethod)
                        <div class="alert alert-info mb-0">
                            {{ __('Current tenant method is email-based two-factor authentication.') }}
                        </div>
                    @elseif (!$setupData['secret'])
                        <p class="text-muted mb-3">{{ __('Start enrollment to generate a secret key and backup codes.') }}</p>
                        <form method="POST" action="{{ route('tenant.profile.security.authenticator.setup') }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">{{ __('Start Authenticator Setup') }}</button>
                        </form>
                    @else
                        @if ($setupData['needs_verification'])
                            <p class="text-muted mb-2">{{ __('Scan this QR in your authenticator app, then verify with a current 6-digit code.') }}
                            </p>
                            <div class="p-3 rounded border bg-light-subtle mb-3">
                                <div class="d-flex justify-content-center">{!! $setupData['qr_svg'] !!}</div>
                                <p class="mb-0 mt-2 text-break">
                                    <strong>{{ __('Manual key:') }}</strong> {{ $setupData['secret'] }}
                                </p>
                            </div>
                            <form method="POST" action="{{ route('tenant.profile.security.authenticator.verify') }}"
                                class="row g-2 align-items-end">
                                @csrf
                                <div class="col-sm-8">
                                    <label class="form-label" for="code">{{ __('Authenticator Code') }}</label>
                                    <input type="text" name="code" id="code" class="form-control"
                                        maxlength="6" inputmode="numeric" placeholder="123456" required>
                                </div>
                                <div class="col-sm-4">
                                    <button type="submit" class="btn btn-success w-100">{{ __('Verify Setup') }}</button>
                                </div>
                            </form>
                        @else
                            <div class="alert alert-success">
                                {{ __('Authenticator is active and verified for your account.') }}
                            </div>
                        @endif

                        <div class="d-flex flex-wrap gap-2 mt-3">
                            <form method="POST" action="{{ route('tenant.profile.security.authenticator.reset') }}">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger">{{ __('Reset Secret') }}</button>
                            </form>
                            <form method="POST" action="{{ route('tenant.profile.security.backup-codes.regenerate') }}">
                                @csrf
                                <button type="submit"
                                    class="btn btn-outline-primary">{{ __('Regenerate Backup Codes') }}</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-5">
            <div class="card custom-card border-0 shadow-sm h-100">
                <div class="card-header">
                    <h6 class="mb-0">{{ __('Backup Codes') }}</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        {{ __('Store these offline. Each backup code can be used once if authenticator access is lost.') }}
                    </p>
                    <div class="small mb-2">
                        {{ __('Available codes:') }} <strong>{{ $setupData['backup_codes_count'] }}</strong>
                    </div>

                    @if (is_array($recentBackupCodes) && count($recentBackupCodes) > 0)
                        <div class="alert alert-warning">
                            {{ __('These backup codes are shown once. Save them now.') }}
                        </div>
                        <ul class="list-group">
                            @foreach ($recentBackupCodes as $backupCode)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <code>{{ $backupCode }}</code>
                                    <span class="badge bg-info-transparent">{{ __('Single Use') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <div class="alert alert-secondary mb-0">
                            {{ __('No new backup codes to display. Regenerate codes to view a fresh set.') }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
