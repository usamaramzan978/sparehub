@extends('layouts.auth')

@section('content')
    <div class="d-flex align-items-center justify-content-center authentication">
        <div class="col-xl-9 col-md-6 col-11">
            <div class="row authentication-cover-main mx-0 border rounded bg-white">
                <div class="col-xxl-6 col-xl-5 col-lg-12 d-xl-block d-none px-0">
                    <div class="authentication-cover overflow-hidden">
                        <div class="authentication-cover-logo">
                            <a href="javascript:void(0);">
                                <img src="../assets/images/brand-logos/desktop-dark.png" alt=""
                                    class="authentication-brand desktop-dark">
                            </a>
                        </div>
                        <div class="aunthentication-cover-content d-flex align-items-center justify-content-center">
                            <div class="row justify-content-center align-items-center">
                                <div class="col-xl-10">
                                    <div class="rounded bg-white-transparent authentication-sub-content">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <img src="../assets/images/media/media-80.png" alt="img">
                                        </div>
                                        <h2 class="fs-4 mt-3 text-fixed-white fw-semibold text-center">
                                            Securely continue to the right workspace.
                                        </h2>
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
                                    <div class="d-flex align-items-center justify-content-center mb-3">
                                        <span class="auth-icon">
                                            <i class="ri-building-4-line fs-3 text-primary"></i>
                                        </span>
                                    </div>
                                    <p class="h4 fw-semibold mb-0 text-center">Choose Workspace</p>
                                    <p class="mb-0 text-muted fw-normal text-center">
                                        Your account is linked to multiple organizations.
                                    </p>

                                    @if ($errors->any())
                                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form class="mt-4" method="POST" action="{{ route('auth.choose-tenant.submit') }}">
                                        @csrf

                                        <div class="d-grid gap-2">
                                            @foreach ($tenants as $tenant)
                                                <button type="submit" name="tenant_id" value="{{ $tenant['tenant_id'] }}"
                                                    class="btn btn-light border text-start p-3">
                                                    <div class="d-flex align-items-start justify-content-between gap-2">
                                                        <div>
                                                            <div class="fw-semibold text-default">
                                                                {{ $tenant['tenant_name'] }}
                                                            </div>
                                                            <div class="text-muted fs-12">
                                                                {{ (string) $tenant['tenant_id'] }}
                                                            </div>
                                                        </div>
                                                        <i class="ri-arrow-right-line text-primary mt-1"></i>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </div>

                                        <div class="text-center mt-4">
                                            <a href="{{ route('auth.login') }}" class="text-primary fw-medium">
                                                Back to Sign In
                                            </a>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
