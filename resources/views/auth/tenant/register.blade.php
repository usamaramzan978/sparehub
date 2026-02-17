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
                        <div class="col-xxl-8 col-xl-9 col-lg-10 col-md-10 col-sm-10 col-12">
                            <div class="card custom-card shadow-none my-auto">
                                <div class="card-body p-5">
                                    <div class="d-flex align-items-center justify-content-center mb-3"> <span
                                            class="auth-icon"> <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"
                                                id="password">
                                                <path fill="#6446fe"
                                                    d="M59,8H5A1,1,0,0,0,4,9V55a1,1,0,0,0,1,1H59a1,1,0,0,0,1-1V9A1,1,0,0,0,59,8ZM58,54H6V10H58Z"
                                                    class="color1d1f47 svgShape"></path>
                                                <path fill="#6446fe"
                                                    d="M36,35H28a3,3,0,0,1-3-3V27a3,3,0,0,1,3-3h8a3,3,0,0,1,3,3v5A3,3,0,0,1,36,35Zm-8-9a1,1,0,0,0-1,1v5a1,1,0,0,0,1,1h8a1,1,0,0,0,1-1V27a1,1,0,0,0-1-1Z"
                                                    class="color0055ff svgShape"></path>
                                                <path fill="#6446fe"
                                                    d="M36 26H28a1 1 0 0 1-1-1V24a5 5 0 0 1 10 0v1A1 1 0 0 1 36 26zm-7-2h6a3 3 0 0 0-6 0zM32 31a1 1 0 0 1-1-1V29a1 1 0 0 1 2 0v1A1 1 0 0 1 32 31z"
                                                    class="color0055ff svgShape"></path>
                                                <path fill="#6446fe"
                                                    d="M59 8H5A1 1 0 0 0 4 9v8a1 1 0 0 0 1 1H20.08a1 1 0 0 0 .63-.22L25.36 14H59a1 1 0 0 0 1-1V9A1 1 0 0 0 59 8zm-1 4H25l-.21 0a1.09 1.09 0 0 0-.42.2L19.73 16H6V10H58zM50 49H14a1 1 0 0 1-1-1V39a1 1 0 0 1 1-1H50a1 1 0 0 1 1 1v9A1 1 0 0 1 50 49zM15 47H49V40H15z"
                                                    class="color1d1f47 svgShape"></path>
                                                <circle cx="19.5" cy="43.5" r="1.5" fill="#6446fe"
                                                    class="color0055ff svgShape"></circle>
                                                <circle cx="24.5" cy="43.5" r="1.5" fill="#6446fe"
                                                    class="color0055ff svgShape"></circle>
                                                <circle cx="29.5" cy="43.5" r="1.5" fill="#6446fe"
                                                    class="color0055ff svgShape"></circle>
                                                <circle cx="34.5" cy="43.5" r="1.5" fill="#6446fe"
                                                    class="color0055ff svgShape"></circle>
                                                <circle cx="39.5" cy="43.5" r="1.5" fill="#6446fe"
                                                    class="color0055ff svgShape"></circle>
                                                <circle cx="44.5" cy="43.5" r="1.5" fill="#6446fe"
                                                    class="color0055ff svgShape"></circle>
                                                <path fill="#6446fe"
                                                    d="M60 9a1 1 0 0 0-1-1H28.81l2.37-2.37A19.22 19.22 0 0 1 60 31zM35.19 56l-2.37 2.37A19.22 19.22 0 0 1 4 33V55a1 1 0 0 0 1 1z"
                                                    opacity=".3" class="color0055ff svgShape"></path>
                                            </svg> </span> </div>
                                    <p class="h4 fw-semibold mb-0 text-center">Create Account</p>
                                    <p class="mb-0 text-muted fw-normal text-center">Set up your tenant user access.</p>
                                    @if ($errors->any())
                                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif
                                    <form class="row gy-3 mt-2" method="POST" action="{{ route('auth.register.submit') }}">
                                        @csrf
                                        <div class="col-xl-12">
                                            <label for="register-name" class="form-label text-default">Full Name</label>
                                            <div class="position-relative">
                                                <input type="text" class="form-control form-control-lg" id="register-name"
                                                    name="name" value="{{ old('name') }}" placeholder="Enter full name"
                                                    autocomplete="name" required>
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <label for="register-email" class="form-label text-default">Email</label>
                                            <div class="position-relative">
                                                <input type="email" class="form-control form-control-lg" id="register-email"
                                                    name="email" value="{{ old('email') }}" placeholder="Enter email address"
                                                    autocomplete="email" required>
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <label for="register-phone" class="form-label text-default">Phone (Optional)</label>
                                            <div class="position-relative">
                                                <input type="text" class="form-control form-control-lg" id="register-phone"
                                                    name="phone" value="{{ old('phone') }}" placeholder="Enter phone number"
                                                    autocomplete="tel">
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <label for="register-password" class="form-label text-default">Password</label>
                                            <div class="position-relative">
                                                <input type="password" class="form-control form-control-lg"
                                                    id="register-password" name="password" placeholder="Create password"
                                                    autocomplete="new-password" required>
                                                <a href="javascript:void(0);" class="show-password-button text-muted"
                                                    data-target="register-password" id="button-addon2"><i
                                                        class="ri-eye-off-line align-middle"></i></a>
                                            </div>
                                        </div>
                                        <div class="col-xl-12">
                                            <label for="register-password-confirm" class="form-label text-default">Confirm
                                                Password</label>
                                            <div class="position-relative">
                                                <input type="password" class="form-control form-control-lg"
                                                    id="register-password-confirm" name="password_confirmation"
                                                    placeholder="Confirm password" autocomplete="new-password" required>
                                                <a href="javascript:void(0);" class="show-password-button text-muted"
                                                    data-target="register-password-confirm" id="button-addon22"><i
                                                        class="ri-eye-off-line align-middle"></i></a>
                                            </div>
                                        </div>
                                        <div class="col-12 d-grid mt-4">
                                            <button type="submit" class="btn btn-primary btn-lg">Create Account</button>
                                        </div>
                                    </form>
                                    <div class="text-center mb-0">
                                        <p class="text-muted mt-3 mb-0">Already have an account? <a
                                                href="{{ route('auth.login') }}" class="text-primary">Sign In</a></p>
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
