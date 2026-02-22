@extends('layouts.auth')

@section('content')
    <div class="d-flex align-items-center justify-content-center authentication">
        <div class="col-xl-9 col-md-6 col-11">
            <div class="row  authentication-cover-main mx-0 border rounded bg-white">
                <div class="col-xxl-6 col-xl-5 col-lg-12 d-xl-block d-none px-0">
                    <div class="authentication-cover overflow-hidden">
                        <div class="authentication-cover-logo"> <a href="index.html"> <img
                                    src="../assets/images/brand-logos/desktop-dark.png" alt=""
                                    class="authentication-brand desktop-dark"> </a> </div>
                        <div class="aunthentication-cover-content d-flex align-items-center justify-content-center">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-xl-10">
                                    <div class="rounded bg-white-transparent authentication-sub-content">
                                        <div class="d-flex align-items-center justify-content-center"> <img
                                                src="../assets/images/media/media-80.png" alt="img"> </div>
                                        <h2 class="fs-4 mt-3 text-fixed-white fw-semibold text-center">"Photography is a way
                                            of feeling, of touching, of loving. What you have caught on film is captured
                                            forever."</h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xxl-6 col-xl-7">
                    <div class="row justify-content-center align-items-center h-100">
                        <div class="col-xxl-8 col-xl-9 col-lg-12 col-md-12 col-sm-12 col-12">
                            <div class="card custom-card shadow-none my-auto">
                                <div class="card-body p-5">
                                    <div class="d-flex align-items-center justify-content-center mb-3"> <span
                                            class="auth-icon"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"
                                                id="filedata">
                                                <g fill="#000000" class="color000000 svgShape">
                                                    <path fill="#6446fe"
                                                        d="M52 6a6 6 0 0 0-4.47 2l-.08.09a1.56 1.56 0 0 0-.16.2 2.14 2.14 0 0 0-.19.26 2.61 2.61 0 0 0-.2.3c-.06.09-.11.18-.16.27a1.39 1.39 0 0 0-.11.21 4 4 0 0 0-.21.47c0 .09-.07.18-.1.27l-.06.2a3.84 3.84 0 0 0-.16.67 2 2 0 0 0-.05.35A4.17 4.17 0 0 0 46 12v1a1 1 0 0 0 1 1H57a1 1 0 0 0 1-1V12A6 6 0 0 0 52 6zm-4 6a4 4 0 0 1 8 0zM42 56a3.82 3.82 0 0 1-.9-.1l-.29-.08a4.09 4.09 0 0 1-.71-.3l-.32-.19a3.32 3.32 0 0 1-.47-.37l0 0a3.25 3.25 0 0 1-.26-.27 2.5 2.5 0 0 1-.26-.33 1.92 1.92 0 0 1-.23-.35 1.74 1.74 0 0 1-.1-.19 2.56 2.56 0 0 1-.11-.24 2.08 2.08 0 0 1-.12-.32 1.27 1.27 0 0 1-.06-.18 1.42 1.42 0 0 1 0-.22A3.84 3.84 0 0 1 38 52V51a1 1 0 0 0-1-1H7a1 1 0 0 0-1 1v1a6 6 0 0 0 6 6H42a1 1 0 0 0 0-2zM12 56a4 4 0 0 1-4-4H36a5.44 5.44 0 0 0 0 .57 2.54 2.54 0 0 0 .07.47 1.65 1.65 0 0 0 .06.32c0 .16.08.3.12.45l0 .08a4.87 4.87 0 0 0 .21.53.89.89 0 0 0 .08.17 1.63 1.63 0 0 0 .11.23 1.82 1.82 0 0 0 .15.27l.27.41c.11.15.23.3.35.44l.05.06z"
                                                        class="color1d1f47 svgShape"></path>
                                                    <path fill="#6446fe"
                                                        d="M38.14,53.05h0a.83.83,0,0,1-.06-.21A1.42,1.42,0,0,0,38.14,53.05ZM52,6H17a6,6,0,0,0-6,6V51a1,1,0,0,0,1,1H36a5.44,5.44,0,0,0,0,.57,2.54,2.54,0,0,0,.07.47,1.65,1.65,0,0,0,.06.32c0,.16.08.3.12.45l0,.08a4.87,4.87,0,0,0,.21.53.89.89,0,0,0,.08.17,1.63,1.63,0,0,0,.11.23,1.82,1.82,0,0,0,.15.27l.27.41c.11.15.23.3.35.44l.05.06a5.93,5.93,0,0,0,.43.44A6,6,0,0,0,42,58h0l.57,0A6.19,6.19,0,0,0,48,51.78V12a4,4,0,0,1,4-4,1,1,0,0,0,0-2ZM46,51.78A4.15,4.15,0,0,1,42.4,56L42,56a3.82,3.82,0,0,1-.9-.1l-.29-.08a3.15,3.15,0,0,1-.71-.3,3.37,3.37,0,0,1-.32-.19,3.32,3.32,0,0,1-.47-.37l0,0a3.25,3.25,0,0,1-.26-.27,2.1,2.1,0,0,1-.26-.33,2.64,2.64,0,0,1-.23-.35,1.14,1.14,0,0,1-.1-.19,2.56,2.56,0,0,1-.11-.24,2.08,2.08,0,0,1-.12-.32l0-.09s0-.07,0-.1a.83.83,0,0,1-.06-.21A3.84,3.84,0,0,1,38,52V51a1,1,0,0,0-1-1H13V12a4,4,0,0,1,4-4H47.53l-.08.09a1.56,1.56,0,0,0-.16.2,2.14,2.14,0,0,0-.19.26,2.61,2.61,0,0,0-.2.3c-.06.09-.11.18-.16.27a1.39,1.39,0,0,0-.11.21,4,4,0,0,0-.21.47c0,.09-.07.18-.1.27l-.06.2a3.84,3.84,0,0,0-.16.67,2,2,0,0,0-.05.35A4.17,4.17,0,0,0,46,12Zm-7.91,1a1.42,1.42,0,0,0,0,.22h0A.83.83,0,0,1,38.09,52.83Z"
                                                        class="color1d1f47 svgShape"></path>
                                                    <path fill="#6446fe"
                                                        d="M35,41H23a3,3,0,0,1-3-3V30a3,3,0,0,1,3-3H35a3,3,0,0,1,3,3v8A3,3,0,0,1,35,41ZM23,29a1,1,0,0,0-1,1v8a1,1,0,0,0,1,1H35a1,1,0,0,0,1-1V30a1,1,0,0,0-1-1Z"
                                                        class="color0055ff svgShape"></path>
                                                    <path fill="#6446fe"
                                                        d="M34 29H24a1 1 0 0 1-1-1V25a6 6 0 0 1 12 0v3A1 1 0 0 1 34 29zm-9-2h8V25a4 4 0 0 0-8 0zM28 37a1 1 0 0 1-.71-.29l-2-2a1 1 0 0 1 1.42-1.42L28 34.59l3.29-3.3a1 1 0 0 1 1.42 1.42l-4 4A1 1 0 0 1 28 37z"
                                                        class="color0055ff svgShape"></path>
                                                    <path fill="#6446fe"
                                                        d="M52,6a6,6,0,0,0-4.47,2l-.08.09a1.56,1.56,0,0,0-.16.2,2.14,2.14,0,0,0-.19.26,2.61,2.61,0,0,0-.2.3c-.06.09-.11.18-.16.27a1.39,1.39,0,0,0-.11.21,4,4,0,0,0-.21.47c0,.09-.07.18-.1.27l-.06.2a3.84,3.84,0,0,0-.16.67,2,2,0,0,0-.05.35A4.17,4.17,0,0,0,46,12v1a1,1,0,0,0,1,1H57a1,1,0,0,0,1-1V12A6,6,0,0,0,52,6Zm-4,6a4,4,0,0,1,8,0Z"
                                                        class="color1d1f47 svgShape"></path>
                                                    <path fill="#6446fe"
                                                        d="M33.19 58l-.37.37A19.23 19.23 0 0 1 5.63 31.18L11 25.81V50H7a1 1 0 0 0-1 1v1a6 6 0 0 0 6 6zM64 19.23a19.16 19.16 0 0 1-5.63 13.59L48 43.19V14h9a1 1 0 0 0 1-1V12a6 6 0 0 0-6-6H30.81l.37-.37A19.23 19.23 0 0 1 64 19.23z"
                                                        opacity=".3" class="color0055ff svgShape"></path>
                                                </g>
                                            </svg> </span> </div>
                                    <p class="h4 fw-semibold mb-0 text-center">Verify Your Account</p>
                                    <p class="mb-4 text-muted fw-normal text-center">
                                        @if ($method === \App\Enums\TwoFactorMethod::AUTHENTICATOR)
                                            Enter the 6 digit code from your authenticator app.
                                        @else
                                            Enter the 6 digit code sent to your email.
                                        @endif
                                    </p>
                                    @if (session('status'))
                                        <div class="alert alert-success mt-3 mb-0" role="alert">
                                            {{ session('status') }}
                                        </div>
                                    @endif
                                    @if ($errors->any())
                                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <form class="row gy-3 mt-2" method="POST"
                                        action="{{ route('tenant.two-step.verify') }}">
                                        @csrf
                                        <div class="col-xl-12 mb-2">
                                            <div class="row">
                                                <div class="col-2"> <input type="text" class="form-control text-center"
                                                        name="code[]" id="one" maxlength="1" inputmode="numeric"
                                                        autocomplete="one-time-code">
                                                </div>
                                                <div class="col-2"> <input type="text" class="form-control text-center"
                                                        name="code[]" id="two" maxlength="1" inputmode="numeric"
                                                        autocomplete="one-time-code">
                                                </div>
                                                <div class="col-2"> <input type="text" class="form-control text-center"
                                                        name="code[]" id="three" maxlength="1" inputmode="numeric"
                                                        autocomplete="one-time-code">
                                                </div>
                                                <div class="col-2"> <input type="text" class="form-control text-center"
                                                        name="code[]" id="four" maxlength="1" inputmode="numeric"
                                                        autocomplete="one-time-code"> </div>
                                                <div class="col-2"> <input type="text" class="form-control text-center"
                                                        name="code[]" id="five" maxlength="1" inputmode="numeric"
                                                        autocomplete="one-time-code"> </div>
                                                <div class="col-2"> <input type="text" class="form-control text-center"
                                                        name="code[]" id="six" maxlength="1" inputmode="numeric"
                                                        autocomplete="one-time-code"> </div>
                                            </div>
                                            <div class="form-check mt-2"> <input class="form-check-input" type="checkbox"
                                                    value="" id="defaultCheck1"> <label
                                                    class="form-check-label text-muted fw-normal fs-12" for="defaultCheck1">
                                                    Did not receive a code? <a href="{{ route('auth.login') }}"
                                                        class="text-primary ms-2 d-inline-block fw-medium">Back to login</a>
                                                </label> </div>
                                        </div>
                                        <div class="col-xl-12 d-grid mt-3">
                                            <button type="submit" class="btn btn-primary btn-lg">Verify</button>
                                        </div>
                                    </form>
                                    @if ($method === \App\Enums\TwoFactorMethod::AUTHENTICATOR)
                                        <div class="text-center my-3 text-muted fw-semibold">{{ __('OR') }}</div>
                                        <form method="POST" action="{{ route('tenant.two-step.verify') }}"
                                            class="row gy-2">
                                            @csrf
                                            <div class="col-12">
                                                <label class="form-label">{{ __('Use Backup Code') }}</label>
                                                <input type="text" name="backup_code" class="form-control"
                                                    placeholder="AB12-CD34" autocomplete="one-time-code">
                                                <small class="text-muted">{{ __('Format is case-insensitive.') }}</small>
                                            </div>
                                            <div class="col-12 d-grid">
                                                <button type="submit"
                                                    class="btn btn-outline-primary">{{ __('Verify With Backup Code') }}</button>
                                            </div>
                                        </form>
                                    @endif
                                    <div class="text-center">
                                        <p class="fs-12 text-danger mt-3 mb-0"><sup><i class="ri-asterisk"></i></sup>Don't
                                            share the verification code with anyone !</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
