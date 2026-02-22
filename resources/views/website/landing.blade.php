@extends('layouts.website')

@section('content')
    <div class="website-landing">
        <header class="website-landing__header py-3">
            <div class="container-xl d-flex justify-content-between align-items-center gap-3 flex-wrap">
                <div class="d-flex align-items-center gap-2">
                    <div class="website-landing__brand-icon">
                        <i class="ri-tools-line fs-18"></i>
                    </div>
                    <span class="fw-semibold fs-5">{{ config('app.name', 'SpareHub') }}</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('auth.login') }}" class="btn btn-outline-primary btn-sm">{{ __('Tenant Login') }}</a>
                    <a href="{{ route('system.login') }}" class="btn btn-primary btn-sm">{{ __('System Login') }}</a>
                </div>
            </div>
        </header>

        <section class="website-landing__hero py-5 py-xl-6">
            <div class="container-xl">
                <div class="row g-4 align-items-center">
                    <div class="col-lg-7">
                        <span class="website-landing__badge">{{ __('Multi-Tenant ERP + Workshop SaaS') }}</span>
                        <h1 class="website-landing__hero-title mt-3 mb-3">
                            {{ __('Run Sales, Workshop, Inventory, and Finance Per Tenant With Full Control') }}
                        </h1>
                        <p class="website-landing__hero-text mb-4">
                            {{ __('SpareHub gives each tenant isolated data, branch-aware operations, role-based access, two-factor security, and audit timeline visibility for sensitive actions.') }}
                        </p>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('auth.login') }}" class="btn btn-primary btn-wave px-4">{{ __('Start With Tenant Login') }}</a>
                            <a href="{{ route('system.login') }}" class="btn btn-outline-secondary btn-wave px-4">{{ __('Open System Panel') }}</a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="card custom-card border-0 shadow-sm website-landing__highlight-card">
                            <div class="card-body p-4">
                                <h6 class="mb-3">{{ __('Platform Highlights') }}</h6>
                                <ul class="list-unstyled mb-0 d-grid gap-2">
                                    <li><i class="ri-check-line text-success me-2"></i>{{ __('Tenant-level database isolation') }}</li>
                                    <li><i class="ri-check-line text-success me-2"></i>{{ __('Branch-scoped transactions and reports') }}</li>
                                    <li><i class="ri-check-line text-success me-2"></i>{{ __('Email + Authenticator two-step verification') }}</li>
                                    <li><i class="ri-check-line text-success me-2"></i>{{ __('Recent activity timeline for critical events') }}</li>
                                    <li><i class="ri-check-line text-success me-2"></i>{{ __('Deployment smoke checks and safer releases') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4 py-xl-5">
            <div class="container-xl">
                <div class="website-landing__section-head mb-3">
                    <h3>{{ __('Core Modules') }}</h3>
                    <p>{{ __('Everything your workshop and spare parts business needs in one connected SaaS workspace.') }}</p>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 col-xl-3">
                        <div class="card custom-card h-100 border-0 shadow-sm website-landing__module-card">
                            <div class="card-body">
                                <h6 class="mb-2">{{ __('Sales + POS') }}</h6>
                                <p class="text-muted mb-0 small">{{ __('Fast billing, mixed product/service lines, payment tracking, and printable invoices.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card custom-card h-100 border-0 shadow-sm website-landing__module-card">
                            <div class="card-body">
                                <h6 class="mb-2">{{ __('Purchases + Vendors') }}</h6>
                                <p class="text-muted mb-0 small">{{ __('Purchase flows, vendor payments, returns, and branch-aware payable visibility.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card custom-card h-100 border-0 shadow-sm website-landing__module-card">
                            <div class="card-body">
                                <h6 class="mb-2">{{ __('Workshop + Job Cards') }}</h6>
                                <p class="text-muted mb-0 small">{{ __('Customer vehicles, job card lifecycle, services/parts, and operations history.') }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-xl-3">
                        <div class="card custom-card h-100 border-0 shadow-sm website-landing__module-card">
                            <div class="card-body">
                                <h6 class="mb-2">{{ __('Inventory + Controls') }}</h6>
                                <p class="text-muted mb-0 small">{{ __('Tracked stock movements, manual adjustments, low-stock visibility, and branch context guard.') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4 py-xl-5">
            <div class="container-xl">
                <div class="row d-flex align-items-center justify-content-center mb-4">
                    <div class="col-xl-8 col-lg-10">
                        <div class="text-center">
                            <h3 class="website-landing__pricing-title fw-semibold">{{ __('Pricing') }}</h3>
                            <h5 class="d-block">{{ __('Plans that scale with your workshop operations') }}</h5>
                            <p class="text-muted mb-0">{{ __('Choose the right SpareHub plan for your team size, branches, and security needs.') }}</p>
                        </div>
                    </div>
                </div>
                <div class="row justify-content-center g-3">
                    <div class="col-lg-8 col-xl-4 col-md-8 col-sm-12">
                        <div class="card custom-card pricing-card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex gap-3 align-items-center p-1">
                                    <div class="pricing-card__icon pricing-card__icon-basic"><i class="ri-store-2-line"></i></div>
                                    <div class="text-start">
                                        <h4 class="fw-medium mb-1">{{ __('Starter') }}</h4>
                                        <span class="mb-1 text-muted d-block">{{ __('Single branch essentials for new teams') }}</span>
                                    </div>
                                </div>
                                <hr class="border-top my-4">
                                <h2 class="mb-0 fw-bold d-block text-secondary">${{ __('29') }}<span class="fs-12 text-secondary fw-medium ms-1">/ {{ __('month') }}</span></h2>
                                <span class="text-muted fs-14">${{ __('3') }}/{{ __('user/month') }}</span>
                                <ul class="list-unstyled pricing-body mt-3">
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('1 branch workspace') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('POS, sales, and purchase modules') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Email OTP two-step verification') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 col-xl-4 col-md-8 col-sm-12">
                        <div class="card custom-card pricing-card h-100 pricing-card--recommended">
                            <div class="card-body p-4">
                                <div class="d-flex gap-3 align-items-center p-1">
                                    <div class="pricing-card__icon pricing-card__icon-pro"><i class="ri-shield-star-line"></i></div>
                                    <div class="text-start">
                                        <h4 class="fw-medium mb-1">{{ __('Growth') }}</h4>
                                        <span class="mb-1 text-muted d-block">{{ __('Most popular for active teams') }}</span>
                                    </div>
                                </div>
                                <hr class="border-top my-4">
                                <h2 class="mb-0 fw-bold d-block text-primary">${{ __('79') }}<span class="fs-12 text-primary fw-medium ms-1">/ {{ __('month') }}</span></h2>
                                <span class="text-muted fs-14">${{ __('5') }}/{{ __('user/month') }}</span>
                                <ul class="list-unstyled pricing-body mt-3">
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Up to 5 branches') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Workshop job cards + service tracking') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Email + authenticator 2FA') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Priority support') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-8 col-xl-4 col-md-8 col-sm-12">
                        <div class="card custom-card pricing-card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex gap-3 align-items-center p-1">
                                    <div class="pricing-card__icon pricing-card__icon-premium"><i class="ri-rocket-2-line"></i></div>
                                    <div class="text-start">
                                        <h4 class="fw-medium mb-1">{{ __('Scale') }}</h4>
                                        <span class="mb-1 text-muted d-block">{{ __('Enterprise-level control') }}</span>
                                    </div>
                                </div>
                                <hr class="border-top my-4">
                                <h2 class="mb-0 fw-bold d-block text-success">${{ __('149') }}<span class="fs-12 text-success fw-medium ms-1">/ {{ __('month') }}</span></h2>
                                <span class="text-muted fs-14">${{ __('8') }}/{{ __('user/month') }}</span>
                                <ul class="list-unstyled pricing-body mt-3">
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Unlimited branches') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Advanced audit timeline + security controls') }}</li>
                                    <li><i class="ri-check-double-line text-success fs-14 me-2"></i>{{ __('Dedicated onboarding and migration help') }}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4 py-xl-5" id="faqs">
            <div class="container-xl">
                <div class="website-landing__section-head mb-3 text-center">
                    <h3>{{ __('Frequently Asked Questions') }}</h3>
                    <p>{{ __('Quick answers for first-time teams evaluating SpareHub.') }}</p>
                </div>
                <div class="row justify-content-center">
                    <div class="col-xl-10">
                        <div class="accordion website-landing__faq-accordion" id="landingFaq">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqOne">
                                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqOneCollapse" aria-expanded="true" aria-controls="faqOneCollapse">
                                        {{ __('Is data isolated per tenant?') }}
                                    </button>
                                </h2>
                                <div id="faqOneCollapse" class="accordion-collapse collapse show" aria-labelledby="faqOne" data-bs-parent="#landingFaq">
                                    <div class="accordion-body">{{ __('Yes. Each tenant uses an isolated tenant database with branch-level access controls.') }}</div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqTwoCollapse" aria-expanded="false" aria-controls="faqTwoCollapse">
                                        {{ __('Can we enforce two-factor authentication?') }}
                                    </button>
                                </h2>
                                <div id="faqTwoCollapse" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#landingFaq">
                                    <div class="accordion-body">{{ __('Yes. Configure 2FA policy at tenant settings and let users verify via email OTP or authenticator app.') }}</div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqThreeCollapse" aria-expanded="false" aria-controls="faqThreeCollapse">
                                        {{ __('Does SpareHub support multi-branch operations?') }}
                                    </button>
                                </h2>
                                <div id="faqThreeCollapse" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#landingFaq">
                                    <div class="accordion-body">{{ __('Yes. Sales, purchases, inventory, reports, and user workflows are branch-scoped for operational accuracy.') }}</div>
                                </div>
                            </div>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqFour">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqFourCollapse" aria-expanded="false" aria-controls="faqFourCollapse">
                                        {{ __('How can we monitor sensitive actions?') }}
                                    </button>
                                </h2>
                                <div id="faqFourCollapse" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#landingFaq">
                                    <div class="accordion-body">{{ __('Use Recent Activity Timeline to track logins, 2FA events, branch switching, and other critical actions.') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4 py-xl-5" id="contact">
            <div class="container-xl">
                <div class="website-landing__section-head mb-3 text-center">
                    <h3>{{ __('Contact Us') }}</h3>
                    <p>{{ __('Need a demo, onboarding guidance, or pricing help? Reach our team directly.') }}</p>
                </div>
                <div class="row g-3">
                    <div class="col-lg-4">
                        <div class="website-landing__contact-card h-100">
                            <div class="website-landing__contact-icon"><i class="ri-customer-service-2-line"></i></div>
                            <h6>{{ __('Sales Team') }}</h6>
                            <p>{{ __('For plan recommendations, branch scaling, and implementation queries.') }}</p>
                            <a class="btn btn-outline-primary btn-sm" href="mailto:sales@sparehub.local">{{ __('Email Sales') }}</a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="website-landing__contact-card h-100">
                            <div class="website-landing__contact-icon"><i class="ri-life-buoy-line"></i></div>
                            <h6>{{ __('Support') }}</h6>
                            <p>{{ __('For product issues, login help, and troubleshooting across modules.') }}</p>
                            <a class="btn btn-outline-primary btn-sm" href="mailto:support@sparehub.local">{{ __('Contact Support') }}</a>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="website-landing__contact-card h-100">
                            <div class="website-landing__contact-icon"><i class="ri-calendar-check-line"></i></div>
                            <h6>{{ __('Book a Demo') }}</h6>
                            <p>{{ __('Get a guided product walkthrough for your workshop and operations team.') }}</p>
                            <a class="btn btn-primary btn-sm" href="{{ route('auth.login') }}">{{ __('Start Demo Flow') }}</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-4 py-xl-5">
            <div class="container-xl">
                <div class="card custom-card border-0 shadow-sm website-landing__final-cta">
                    <div class="card-body p-4 p-xl-5">
                        <div class="row g-4 align-items-center">
                            <div class="col-lg-8">
                                <h4 class="mb-2">{{ __('Security and Operational Accountability Built In') }}</h4>
                                <p class="text-muted mb-0">{{ __('Two-factor policy is tenant-controlled, enrollment is user-level, and important actions are captured in a tenant-specific activity timeline for clear traceability.') }}</p>
                            </div>
                            <div class="col-lg-4 text-lg-end d-flex flex-wrap justify-content-lg-end gap-2">
                                <a href="{{ route('auth.login') }}" class="btn btn-primary">{{ __('Open SpareHub') }}</a>
                                <a href="{{ route('system.login') }}" class="btn btn-outline-primary">{{ __('Admin Panel') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('styles')
    <style>
        .website-landing {
            --landing-bg: radial-gradient(circle at 10% 8%, rgba(33, 99, 255, 0.12), transparent 30%), radial-gradient(circle at 92% 18%, rgba(10, 185, 129, 0.12), transparent 26%), linear-gradient(150deg, #f4f8ff 0%, #f7f6ef 52%, #eef4ff 100%);
            --landing-border: rgba(12, 34, 64, 0.1);
            --landing-text: #0f172a;
            --landing-muted: #5b6478;
            --landing-primary: #3b66f5;
            min-height: calc(100vh - 1px);
            background: var(--landing-bg);
            color: var(--landing-text);
        }

        .website-landing__header {
            border-bottom: 1px solid var(--landing-border);
            background: rgba(255, 255, 255, 0.84);
            backdrop-filter: blur(6px);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .website-landing__brand-icon {
            width: 30px;
            height: 30px;
            border-radius: 9px;
            display: inline-flex;
            justify-content: center;
            align-items: center;
            color: #fff;
            background: linear-gradient(140deg, #4f6ef7, #24a6ff);
            box-shadow: 0 10px 22px rgba(66, 103, 242, 0.26);
        }

        .website-landing__badge {
            display: inline-flex;
            align-items: center;
            border: 1px solid rgba(59, 102, 245, 0.35);
            background: rgba(59, 102, 245, 0.08);
            color: #2d4bcc;
            border-radius: 999px;
            padding: 0.34rem 0.8rem;
            font-size: 0.76rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .website-landing__hero {
            border-bottom: 1px solid var(--landing-border);
        }

        .website-landing__hero-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            line-height: 1.1;
            max-width: 16ch;
        }

        .website-landing__hero-text {
            color: var(--landing-muted);
            font-size: 1.03rem;
            max-width: 55ch;
        }

        .website-landing__highlight-card,
        .website-landing__module-card,
        .website-landing__contact-card,
        .website-landing__faq-accordion .accordion-item {
            border: 1px solid var(--landing-border);
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.9);
        }

        .website-landing__section-head h3 {
            margin: 0;
            font-size: clamp(1.35rem, 2.4vw, 1.9rem);
            font-weight: 800;
        }

        .website-landing__section-head p {
            margin: 0.42rem 0 0;
            color: var(--landing-muted);
        }

        .website-landing__module-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .website-landing__module-card:hover,
        .pricing-card:hover,
        .website-landing__contact-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(17, 24, 39, 0.08);
        }

        .website-landing__pricing-title {
            color: var(--landing-primary);
        }

        .pricing-card {
            border-radius: 16px;
            border: 1px solid var(--landing-border);
            background: rgba(255, 255, 255, 0.9);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .pricing-card--recommended {
            border-color: rgba(59, 102, 245, 0.4);
            box-shadow: 0 16px 30px rgba(59, 102, 245, 0.12);
        }

        .pricing-card__icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1px solid;
            font-size: 1.3rem;
        }

        .pricing-card__icon-basic {
            color: #475569;
            border-color: rgba(71, 85, 105, 0.25);
            background: rgba(71, 85, 105, 0.08);
        }

        .pricing-card__icon-pro {
            color: #1d4ed8;
            border-color: rgba(29, 78, 216, 0.25);
            background: rgba(37, 99, 235, 0.09);
        }

        .pricing-card__icon-premium {
            color: #047857;
            border-color: rgba(4, 120, 87, 0.25);
            background: rgba(16, 185, 129, 0.09);
        }

        .pricing-body li {
            display: flex;
            align-items: center;
            margin-bottom: 0.55rem;
            font-size: 0.9rem;
            color: #334155;
        }

        .website-landing__faq-accordion .accordion-item {
            overflow: hidden;
            margin-bottom: 0.75rem;
        }

        .website-landing__faq-accordion .accordion-button {
            font-weight: 600;
            background: rgba(255, 255, 255, 0.9);
        }

        .website-landing__faq-accordion .accordion-button:not(.collapsed) {
            background: rgba(59, 102, 245, 0.08);
            color: #1e40af;
            box-shadow: none;
        }

        .website-landing__faq-accordion .accordion-body {
            color: var(--landing-muted);
        }

        .website-landing__contact-card {
            padding: 1.15rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .website-landing__contact-icon {
            width: 42px;
            height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #2563eb;
            background: rgba(37, 99, 235, 0.1);
            margin-bottom: 0.7rem;
        }

        .website-landing__contact-card p {
            color: var(--landing-muted);
            margin-bottom: 0.9rem;
        }

        .website-landing__final-cta {
            border-radius: 16px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.96), rgba(244, 249, 255, 0.96));
        }

        .py-xl-6 {
            padding-top: 5rem;
            padding-bottom: 5rem;
        }

        @media (max-width: 991.98px) {
            .py-xl-6 {
                padding-top: 3rem;
                padding-bottom: 3rem;
            }

            .website-landing__header {
                position: static;
            }
        }
    </style>
@endpush
