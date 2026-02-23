<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Actions\Tenant\Profile\BuildProfileSecurityDataAction;
use App\Actions\Tenant\Profile\BuildProfileShowDataAction;
use App\Actions\Tenant\Profile\ProfileAuthenticatorServiceAction;
use App\Actions\Tenant\Profile\RegenerateProfileBackupCodesAction;
use App\Actions\Tenant\Profile\ResetProfileAuthenticatorAction;
use App\Actions\Tenant\Profile\SetupProfileAuthenticatorAction;
use App\Actions\Tenant\Profile\UpdateProfileAction;
use App\Actions\Tenant\Profile\VerifyProfileAuthenticatorAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateProfileRequest;
use App\Http\Requests\Tenant\VerifyAuthenticatorEnrollmentRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class ProfileController extends Controller
{
    public function show(BuildProfileShowDataAction $buildProfileShowDataAction): View
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        return view('tenants.profile.show', $buildProfileShowDataAction->handle($user));
    }

    public function edit(): View
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        return view('tenants.profile.edit', ['user' => $user]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $updateProfileAction): RedirectResponse
    {
        $user = Auth::guard('user')->user();
        abort_if($user === null, 403);

        $updateProfileAction->handle($user, $request->validated());

        return to_route('tenant.profile.show')
            ->with('status', 'Profile updated.');
    }

    public function security(BuildProfileSecurityDataAction $buildProfileSecurityDataAction): View
    {
        $user = $this->authenticatedFreshUser();

        return view('tenants.profile.security', $buildProfileSecurityDataAction->handle(
            $user,
            $this->currentBranchId(),
            session('security.backup_codes')
        ));
    }

    public function setupAuthenticator(
        ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction,
        SetupProfileAuthenticatorAction $setupProfileAuthenticatorAction
    ): RedirectResponse {
        $user = $this->authenticatedFreshUser();

        if (! $profileAuthenticatorServiceAction->authenticatorEnabledForBranch($this->currentBranchId())) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        $backupCodes = $setupProfileAuthenticatorAction->handle($user);

        return to_route('tenant.profile.security.show')
            ->with('status', 'Authenticator setup started. Scan the QR code and verify once.')
            ->with('security.backup_codes', $backupCodes);
    }

    public function verifyAuthenticator(
        VerifyAuthenticatorEnrollmentRequest $request,
        ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction,
        VerifyProfileAuthenticatorAction $verifyProfileAuthenticatorAction
    ): RedirectResponse {
        $user = $this->authenticatedFreshUser();

        if (! $profileAuthenticatorServiceAction->authenticatorEnabledForBranch($this->currentBranchId())) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        $result = $verifyProfileAuthenticatorAction->handle($user, (string) $request->string('code')->toString(), $request);
        if (! $result['ok']) {
            return to_route('tenant.profile.security.show')
                ->withErrors([(string) $result['error_key'] => (string) $result['error_message']]);
        }

        if (($result['redirect_dashboard'] ?? false) === true) {
            return to_route('tenant.dashboard')
                ->with('status', 'Authenticator setup complete.');
        }

        return to_route('tenant.profile.security.show')
            ->with('status', 'Authenticator setup complete.');
    }

    public function resetAuthenticator(
        ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction,
        ResetProfileAuthenticatorAction $resetProfileAuthenticatorAction
    ): RedirectResponse {
        $user = $this->authenticatedFreshUser();

        if (! $profileAuthenticatorServiceAction->authenticatorEnabledForBranch($this->currentBranchId())) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        $backupCodes = $resetProfileAuthenticatorAction->handle($user);

        return to_route('tenant.profile.security.show')
            ->with('status', 'Authenticator secret has been reset. Verify with a fresh app code.')
            ->with('security.backup_codes', $backupCodes);
    }

    public function regenerateBackupCodes(
        ProfileAuthenticatorServiceAction $profileAuthenticatorServiceAction,
        RegenerateProfileBackupCodesAction $regenerateProfileBackupCodesAction
    ): RedirectResponse {
        $user = $this->authenticatedFreshUser();

        if (! $profileAuthenticatorServiceAction->authenticatorEnabledForBranch($this->currentBranchId())) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Authenticator method is not enabled in tenant settings.']);
        }

        if (! $user->two_factor_secret) {
            return to_route('tenant.profile.security.show')
                ->withErrors(['security' => 'Start authenticator setup before generating backup codes.']);
        }

        $backupCodes = $regenerateProfileBackupCodesAction->handle($user);

        return to_route('tenant.profile.security.show')
            ->with('status', 'Backup codes regenerated.')
            ->with('security.backup_codes', $backupCodes);
    }

    private function authenticatedFreshUser(): User
    {
        /** @var User|null $user */
        $user = Auth::guard('user')->user()?->fresh();
        abort_if($user === null, 403);

        return $user;
    }
}
