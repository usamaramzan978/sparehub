# Multi-Tenant Authentication System Documentation

## Overview

This document describes a secure multi-tenant authentication system implementation for Laravel with Stancl Tenancy package. The system handles central login with automatic tenant routing and supports users with accounts in multiple tenants.

---

## Architecture Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                     CENTRAL LOGIN (/)                            │
│  User enters: email + password                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │   LoginAction        │
              │  - Validates creds   │
              │  - Finds tenants     │
              └──────────┬───────────┘
                         │
           ┌─────────────┴─────────────┐
           │                           │
    ┌──────▼──────┐           ┌───────▼────────┐
    │ 1 Tenant    │           │ Multiple       │
    │ Found       │           │ Tenants Found  │
    └──────┬──────┘           └───────┬────────┘
           │                           │
           │                  ┌────────▼────────┐
           │                  │ Tenant Picker   │
           │                  │ User Chooses    │
           │                  └────────┬────────┘
           │                           │
           └───────────┬───────────────┘
                       │
          ┌────────────▼─────────────┐
          │  Generate Signed URL     │
          │  with one-time nonce     │
          └────────────┬─────────────┘
                       │
          ┌────────────▼──────────────────┐
          │  /firm/{tenant}/authenticate  │
          │  - Verify signature           │
          │  - Consume nonce              │
          │  - Log user into tenant       │
          └────────────┬──────────────────┘
                       │
          ┌────────────▼─────────────┐
          │  /firm/{tenant}/dashboard │
          │  User is logged in        │
          └───────────────────────────┘
```

---

## Key Components

### 1. LoginAction (Central Credential Validation)

**Location**: `App\Actions\Auth\LoginAction`

**Responsibilities**:
- Validates email and password against central `LoginMap` table
- Handles multiple tenant scenarios
- Implements constant-time password checking for security

**Returns**:
- Single tenant: `['tenant' => ..., 'type_id' => ..., 'type' => ..., 'remember' => ...]`
- Multiple tenants: `['multiple_tenants' => true, 'tenants' => [...], 'remember' => ...]`

---

### 2. CentralAuthController

**Location**: `App\Http\Controllers\Auth\Central\AuthController`

**Methods**:

#### `showLogin()`
- Displays central login form
- Route: `GET /`

#### `login(LoginRequest $request, LoginAction $action)`
- Processes login submission
- Redirects to tenant picker if multiple tenants found
- Generates signed URL for single tenant
- Route: `POST /login`

#### `showChooseTenant()`
- Displays tenant picker UI
- Validates session hasn't expired (5 minute window)
- Route: `GET /choose-tenant`

#### `chooseTenant(Request $request)`
- Processes tenant selection
- Validates selected tenant is in authorized list
- Generates signed URL for chosen tenant
- Route: `POST /choose-tenant`

#### `generateAuthenticateRedirect()` (private)
- Creates one-time nonce
- Stores authentication payload in central cache
- Generates 30-second signed URL
- Returns redirect response

---

### 3. TenantAuthController

**Location**: `App\Http\Controllers\Auth\Tenant\AuthController`

**Method**: `authenticateTenant(Request $request, IssueTwoStepCodeAction $twoStep)`

**Security Checks**:
1. ✅ Validates signed URL signature
2. ✅ Validates nonce is valid UUID format
3. ✅ Consumes nonce atomically (one-time use)
4. ✅ Verifies login type matches expected type
5. ✅ Verifies tenant context matches payload
6. ✅ Verifies user's LoginMap status is active
7. ✅ Verifies user account status is active
8. ✅ Regenerates session to prevent fixation
9. ✅ Applies tenant 2FA policy (email/authenticator) before dashboard redirect

**Route**: `GET /firm/{tenant}/authenticate`

### 4. Tenant 2FA integration (implemented)

Tenant authentication now includes method-based two-step verification:
- Configuration source: `tenant_settings`
  - `two_factor_enabled`
  - `two_factor_method` (`email`, `authenticator`)
- Verification route: `POST /firm/{tenant}/two-step`

Behavior:
- If 2FA disabled: go directly to dashboard after tenant authentication.
- If 2FA method is `email`: send 6-digit email code and require `/two-step` verification.
- If 2FA method is `authenticator`: require 6-digit app code at `/two-step`.

Enrollment model:
- Authenticator QR/manual key is shown in tenant `Settings` during enrollment/setup.
- `/two-step` page is verification-only (does not display enrollment secret material).

---

## Edge Cases Handled

### 1. ✅ Same Email in Multiple Tenants (Different People)

**Scenario**: 
- Tenant A has user `john@gmail.com` (John Smith)
- Tenant B has user `john@gmail.com` (John Doe - different person)

**Solution**: 
- Email uniqueness is **tenant-scoped**, not global
- Each tenant can have any email address
- No collision or blocking occurs

**Implementation**:
```php
// Registration validation (in tenant context)
$existsInThisTenant = LoginMap::query()
    ->where('email', $email)
    ->where('tenant_id', tenant()->getTenantKey())
    ->exists();
```

---

### 2. ✅ Same Person with Accounts in Multiple Tenants

**Scenario**:
- Jane Doe is a freelancer
- She has accounts in:
  - Tenant A (Client Acme) - `jane@gmail.com`
  - Tenant B (Client Beta) - `jane@gmail.com`
  - Both use the same password

**Solution**:
- Login validates credentials against all tenants
- Tenant picker displays all matching organizations
- User chooses which workspace to enter
- System logs them into selected tenant

**UX Flow**:
```
1. Enter jane@gmail.com + password
2. System finds 2 matching tenants
3. Display: "Choose which workspace to access:"
   → Acme Corp
   → Beta Inc
4. User clicks "Acme Corp"
5. Logged into Tenant A
```

---

### 3. ✅ Timing Attack Prevention

**Problem**: Sequential password checking could reveal which tenant has correct password based on response time.

**Solution**: Check password against ALL matching logins before returning result.

**Implementation**:
```php
// ✅ Constant-time checking
$matchedLogins = collect();
foreach ($logins as $login) {
    if (Hash::check($password, (string) $login->password)) {
        $matchedLogins->push($login);
    }
}
// Always iterates through all, regardless of match position
```

---

### 4. ✅ Empty Email Query Timing Attack

**Problem**: If email doesn't exist, returning immediately reveals this fact faster than password checking would take.

**Solution**: Always perform a hash check even when email not found.

**Implementation**:
```php
if ($logins->isEmpty()) {
    // ✅ Dummy hash to maintain constant time
    Hash::check($password, Hash::make('dummy'));
    throw ValidationException::withMessages([...]);
}
```

---

### 5. ✅ Nonce Replay Attack Prevention

**Problem**: Attacker intercepts signed URL and tries to use it multiple times.

**Solution**: Nonce is consumed atomically using `Cache::pull()` which gets and deletes in one operation.

**Implementation**:
```php
$payload = Cache::store('database')->pull("login_nonce:{$nonce}");
// Second call to same nonce returns null
```

**Additional Protection**:
- 30-second expiration on nonce
- Signed URL expires in 30 seconds
- Nonce format validation (must be UUID)

---

### 6. ✅ Session Fixation Prevention

**Problem**: Attacker could pre-set a session ID and trick user into authenticating with it.

**Solution**: Session is regenerated after successful authentication.

**Implementation**:
```php
Auth::guard('user')->login($user, (bool) $payload['remember']);
$request->session()->regenerate(); // ✅ New session ID
```

---

### 7. ✅ Cross-Tenant Login Attempt

**Problem**: User tries to authenticate into Tenant A using credentials from Tenant B.

**Solution**: Payload includes tenant_id, which is verified against current tenant context.

**Implementation**:
```php
abort_if(
    ($payload['tenant_id'] ?? null) !== $tenantId, 
    403, 
    'Invalid tenant context.'
);
```

---

### 8. ✅ Inactive Account Login Prevention

**Problem**: User account is deactivated but login credentials still valid.

**Solution**: Double-check both LoginMap status and User status before allowing login.

**Implementation**:
```php
// Check 1: LoginMap status
$activeLoginMapExists = LoginMap::query()
    ->where('tenant_id', $tenantId)
    ->where('status', true)
    ->exists();

// Check 2: User status
if ($userStatus !== UserStatus::ACTIVE->value) {
    return to_route('tenant.login')
        ->withErrors(['email' => 'Account inactive...']);
}
```

---

### 9. ✅ Type Confusion Attack

**Problem**: Attacker tries to log in as 'system' user type through 'user' endpoint.

**Solution**: Verify login type matches expected type from payload.

**Implementation**:
```php
abort_if(
    ($payload['type'] ?? null) !== LoginUserType::USER->value, 
    403, 
    'Invalid login type.'
);
```

---

### 10. ✅ Cache Tenant Isolation Issue

**Problem**: With `CacheTenancyBootstrapper`, nonce stored in central cache might be read from tenant cache.

**Solution**: 
- Comment out `CacheTenancyBootstrapper` OR
- Use explicit `central` cache connection

**Implementation**:
```php
// Option 1: Disable cache bootstrapper
'bootstrappers' => [
    DatabaseTenancyBootstrapper::class,
    // CacheTenancyBootstrapper::class, // ← Commented out
],

// Option 2: Use central cache store explicitly
Cache::store('central')->put("login_nonce:{$nonce}", $data);
```

---

### 11. ✅ Tenant Picker Session Expiry

**Problem**: User takes too long to select tenant, session data becomes stale.

**Solution**: 5-minute expiration on tenant selection session data.

**Implementation**:
```php
session()->put('multi_tenant_login', [
    'tenants'    => $data['tenants'],
    'remember'   => $data['remember'],
    'expires_at' => now()->addMinutes(5), // ✅ Expire
]);

// Validation on picker page
if (!$data || now()->gt($data['expires_at'])) {
    return redirect()->route('auth.login')
        ->withErrors(['email' => 'Session expired...']);
}
```

---

### 12. ✅ Malformed Nonce Attack

**Problem**: Attacker sends non-UUID nonce to cause cache lookup errors.

**Solution**: Validate nonce format before cache lookup.

**Implementation**:
```php
$nonce = (string) $request->query('nonce');
abort_unless(Str::isUuid($nonce), 403, 'Invalid request.');
```

---

### 13. ✅ Unauthorized Tenant Selection

**Problem**: Attacker modifies POST data to select a tenant they don't have access to.

**Solution**: Verify selected tenant exists in the validated session list.

**Implementation**:
```php
$tenant = collect($data['tenants'])
    ->firstWhere('tenant_id', $selectedTenantId);

if (!$tenant) {
    abort(403, 'Unauthorized tenant selection.');
}
```

---

## Security Features Summary

| Feature | Implementation | Protection Against |
|---------|----------------|---------------------|
| Signed URLs | Laravel's HMAC-SHA256 | Parameter tampering |
| One-time nonces | `Cache::pull()` atomic operation | Replay attacks |
| 30-second expiry | Nonce & signature TTL | Stale link attacks |
| Session regeneration | `session()->regenerate()` | Session fixation |
| Constant-time password check | Check all tenants before returning | Timing attacks |
| Dummy hash on miss | Hash even when email not found | Email enumeration timing |
| Type validation | Verify `type` in payload | Type confusion attacks |
| Tenant context verification | Match payload tenant with URL tenant | Cross-tenant attacks |
| Active status checks | LoginMap + User status validation | Inactive account access |
| UUID nonce validation | `Str::isUuid()` check | Malformed input attacks |
| Session expiry | 5-minute picker timeout | Stale session attacks |
| Rate limiting | `throttle:6,1` on login routes | Brute force attacks |
| Tenant-scoped email | Uniqueness per tenant only | Global email blocking |

---

## Rate Limiting Configuration

```php
// Central login
Route::post('login', [CentralAuthController::class, 'login'])
    ->middleware('throttle:6,1') // 6 attempts per minute
    ->name('auth.login.submit');

// Tenant authenticate endpoint
Route::get('authenticate', [TenantAuthController::class, 'authenticateTenant'])
    ->middleware('throttle:10,1') // 10 attempts per minute
    ->name('authenticate');

// Tenant direct login
Route::post('login', [TenantAuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('auth.login.submit');
```

---

## Database Schema Requirements

### LoginMap Table (Central Database)

```php
Schema::create('login_maps', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email')->index();
    $table->string('password');
    $table->uuid('tenant_id')->index();
    $table->string('type'); // 'user', 'system', etc.
    $table->uuid('type_id'); // References User.id in tenant DB
    $table->boolean('status')->default(true);
    $table->timestamps();
    
    // ✅ Composite unique: same email can exist across tenants
    $table->unique(['email', 'tenant_id', 'type']);
});
```

### User Table (Tenant Database)

```php
Schema::create('users', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('email')->unique(); // ✅ Unique within this tenant
    $table->string('name');
    $table->string('status'); // 'active', 'inactive', etc.
    $table->timestamps();
});
```

---

## Configuration Requirements

### 1. Session Driver

```env
# Recommended: Use file sessions to avoid tenant DB switching issues
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

### 2. Cache Configuration

```php
// config/cache.php
'stores' => [
    'database' => [
        'driver'     => 'database',
        'table'      => 'cache',
        'connection' => null, // Uses default connection
    ],
],
```

### 3. Tenancy Bootstrappers

```php
// config/tenancy.php
'bootstrappers' => [
    \Stancl\Tenancy\Bootstrappers\TenancyDatabaseBootstrapper::class,
    // ✅ Comment this out to prevent cache tenant switching issues
    // \Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
    \Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
],
```

### 4. Auth Guards

```php
// config/auth.php
'guards' => [
    'user' => [
        'driver'   => 'session',
        'provider' => 'users',
    ],
    'system' => [
        'driver'   => 'session',
        'provider' => 'system_users',
    ],
],

'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model'  => App\Models\User::class,
    ],
],
```

### 5. Auth Redirects (Laravel 12)

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->redirectGuestsTo(function (Request $request) {
        if (function_exists('tenant') && tenant()) {
            $tenantId = (string) tenant()->getTenantKey();
            return route('tenant.login', ['tenant' => $tenantId]);
        }
        return route('auth.login');
    });

    $middleware->redirectUsersTo(function (Request $request) {
        if (function_exists('tenant') && tenant()) {
            $tenantId = (string) tenant()->getTenantKey();
            return route('tenant.dashboard', ['tenant' => $tenantId]);
        }
        return route('auth.login');
    });
})
```

---

## Routes Overview

### Central Routes (`routes/web.php`)

```php
Route::middleware('guest')->group(function (): void {
    // Entry point
    Route::get('/', [CentralAuthController::class, 'showLogin'])
        ->name('auth.login');
    
    // Process login
    Route::post('login', [CentralAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('auth.login.submit');
    
    // Tenant picker (when multiple tenants found)
    Route::get('choose-tenant', [CentralAuthController::class, 'showChooseTenant'])
        ->name('auth.choose-tenant');
    
    Route::post('choose-tenant', [CentralAuthController::class, 'chooseTenant'])
        ->name('auth.choose-tenant.submit');
});
```

### Tenant Routes (`routes/tenant.php` or `routes/web.php`)

```php
Route::middleware([
    'web',
    InitializeTenancyByPath::class,
    PreventAccessFromCentralDomains::class,
])->prefix('firm/{tenant}')->name('tenant.')->group(function (): void {

    // One-time token endpoint (outside guest middleware)
    Route::get('authenticate', [TenantAuthController::class, 'authenticateTenant'])
        ->middleware('throttle:10,1')
        ->name('authenticate');

    // Guest routes
    Route::middleware('guest:user')->group(function (): void {
        Route::get('login', [TenantAuthController::class, 'showLogin'])
            ->name('login');
        
        Route::post('login', [TenantAuthController::class, 'login'])
            ->middleware('throttle:6,1')
            ->name('auth.login.submit');
        
        Route::get('register', [TenantAuthController::class, 'showRegister'])
            ->name('register');
        
        Route::post('register', [TenantAuthController::class, 'register'])
            ->name('auth.register.submit');
        
        // Password reset routes...
    });

    // Authenticated routes
    Route::middleware('auth:user')->group(function (): void {
        Route::get('dashboard', [TenantDashboardController::class, 'index'])
            ->name('dashboard');
        
        Route::post('logout', [TenantAuthController::class, 'logout'])
            ->name('logout');
    });
});
```

---

## Testing Checklist

### Basic Flow Tests

- [ ] Single tenant login works
- [ ] Multiple tenant picker appears when needed
- [ ] Tenant selection logs into correct tenant
- [ ] Session persists after login
- [ ] Logout works correctly
- [ ] Remember me functionality works

### Security Tests

- [ ] Expired signed URL is rejected
- [ ] Tampered signed URL is rejected
- [ ] Nonce can only be used once
- [ ] Malformed nonce is rejected
- [ ] Wrong tenant_id in payload is rejected
- [ ] Inactive LoginMap is rejected
- [ ] Inactive User is rejected
- [ ] Wrong login type is rejected
- [ ] Rate limiting works on all endpoints
- [ ] Session regenerates after login

### Edge Case Tests

- [ ] Same email in different tenants both work
- [ ] Picker expires after 5 minutes
- [ ] Back button on picker redirects to login
- [ ] Unauthorized tenant selection is blocked
- [ ] Constant-time password checking (use profiler)
- [ ] Empty email query takes same time as valid email

---

## Troubleshooting

### Issue: "Too many redirects" loop

**Causes**:
1. Session not persisting after login
2. `CacheTenancyBootstrapper` interfering with cache nonce lookup
3. Auth guard mismatch

**Solutions**:
```php
// 1. Check session driver
SESSION_DRIVER=file

// 2. Comment out cache bootstrapper
// CacheTenancyBootstrapper::class,

// 3. Verify guard in routes
Route::middleware('guest:user') // Not just 'guest'
Route::middleware('auth:user')  // Not just 'auth'
```

### Issue: "Route [login] not defined"

**Cause**: `auth` middleware can't find redirect route

**Solution**: Configure in `bootstrap/app.php`:
```php
$middleware->redirectGuestsTo(function (Request $request) {
    // ... tenant-aware redirect logic
});
```

### Issue: Nonce not found / already consumed

**Causes**:
1. Signed URL hit twice (browser prefetch)
2. Cache store misconfigured
3. `CacheTenancyBootstrapper` switching cache context

**Solutions**:
```php
// Use file cache for nonces
Cache::store('file')->put("login_nonce:{$nonce}", ...);

// Or disable cache bootstrapper
// CacheTenancyBootstrapper::class,
```

### Issue: User logged in but wrong tenant

**Cause**: Tenant context not properly initialized

**Solution**: Verify middleware order:
```php
Route::middleware([
    'web',
    InitializeTenancyByPath::class, // Must be before controller
    PreventAccessFromCentralDomains::class,
])
```

---

## Performance Considerations

### 1. Cache Nonce Storage

- Uses database cache by default
- Consider Redis for high-traffic applications
- Nonces auto-expire after 30 seconds

### 2. Password Hashing

- Constant-time checking adds minimal overhead
- Typical delay: 50-100ms per login attempt
- Rate limiting prevents brute force regardless

### 3. Session Storage

- File sessions recommended for simplicity
- Database sessions work but require careful tenant isolation
- Redis sessions best for high-traffic scenarios

---

## Future Enhancements

### Possible Improvements

1. **SSO Integration**: Add SAML/OAuth support for enterprise tenants
2. **Remember Device**: Remember trusted devices to reduce repeated 2FA prompts
3. **Last Used Tenant**: Auto-select user's most recently used tenant
4. **Tenant Favorites**: Let users star/favorite specific tenants
5. **API Token Auth**: Generate tenant-scoped API tokens
6. **Audit Logging**: Track all authentication attempts
7. **Account Linking**: Let users merge accounts across tenants

---

## Conclusion

This authentication system provides:

✅ **Secure** multi-tenant authentication
✅ **Flexible** handling of multi-tenant users
✅ **User-friendly** tenant picker experience
✅ **Robust** protection against common attacks
✅ **Scalable** architecture for growth

The implementation balances security, usability, and maintainability while handling complex edge cases that arise in real-world multi-tenant applications.

---

## Document Version

- **Version**: 1.1
- **Last Updated**: February 21, 2026
- **Laravel Version**: 12.x
- **Stancl Tenancy Version**: 3.x

---

## Contact & Support

For questions or issues with this implementation:
- Review the edge cases section
- Check troubleshooting guide
- Verify configuration requirements
- Test with the provided checklist
