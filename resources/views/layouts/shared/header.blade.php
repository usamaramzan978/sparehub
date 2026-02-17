@php
    $branches = auth()->check() ? \App\Models\Branch::query()->active()->orderBy('name')->get() : collect();
    $currentBranchId = session('tenant.current_branch_id');
@endphp

<header class="app-header sticky" id="header"> <!-- Start::main-header-container -->
    <div class="main-header-container container-fluid"> <!-- Start::header-content-left -->
        <div class="header-content-left"> <!-- Start::header-element -->
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="#" class="header-logo">
                        <img src="../assets/images/brand-logos/toggle-dark.png" alt="logo" class="desktop-logo">
                </div>
            </div>
            <!-- End::header-element -->
            <!-- Start::header-element -->
            <div class="header-element">
                <!-- Start::header-link -->
                <a aria-label="{{ __('Hide Sidebar') }}" class="sidemenu-toggle header-link" data-bs-toggle="sidebar"
                    href="javascript:void(0);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn" width="32"
                        height="32" fill="#000000" viewBox="0 0 256 256">
                        <path
                            d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z">
                        </path>
                    </svg>
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn-close" width="32"
                        height="32" fill="#000000" viewBox="0 0 256 256">
                        <path
                            d="M205.66,194.34a8,8,0,0,1-11.32,11.32L128,139.31,61.66,205.66a8,8,0,0,1-11.32-11.32L116.69,128,50.34,61.66A8,8,0,0,1,61.66,50.34L128,116.69l66.34-66.35a8,8,0,0,1,11.32,11.32L139.31,128Z">
                        </path>
                    </svg>
                </a> <!-- End::header-link -->

                @if ($branches->isNotEmpty())
                    <li class="header-element d-none d-md-block header-branch-switcher">
                        <form action="{{ route('tenant.branch.switch', ['tenant' => tenant('id')]) }}" method="POST">
                            @csrf
                            <select name="branch_id" class="form-select singl-select-2" onchange="this.form.submit()">
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected($currentBranchId === $branch->id)>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                        </form>

                    </li>
                @endif
            </div> <!-- End::header-element -->
        </div> <!-- End::header-content-left --> <!-- Start::header-content-right -->
        <ul class="header-content-right"> <!-- Start::header-element -->
            @php
                $isQuickPosActive = request()->routeIs('tenant.pos.*');
            @endphp
            <li class="header-element d-none d-md-block">
                <a href="{{ route('tenant.pos.index') }}"
                    class="btn btn-sm {{ $isQuickPosActive ? 'btn-primary' : 'btn-outline-primary' }} d-inline-flex align-items-center gap-1"
                    title="{{ __('Quick POS') }}" @if ($isQuickPosActive) aria-current="page" @endif>
                    <i class="ri-shopping-cart-2-line"></i>
                    <span class="d-none d-lg-inline">{{ __('POS') }}</span>
                </a>
            </li>


            <!-- End::header-element --> <!-- Start::header-element -->
            <li class="header-element header-theme-mode">
                <!-- Start::header-link|layout-setting -->
                <a href="javascript:void(0);" class="header-link layout-setting"> <span class="light-layout">
                        <!-- Start::header-link-icon --> <svg xmlns="http://www.w3.org/2000/svg"
                            class="header-link-icon" viewBox="0 0 256 256">
                            <rect width="256" height="256" fill="none"></rect>
                            <path d="M108.11,28.11A96.09,96.09,0,0,0,227.89,147.89,96,96,0,1,1,108.11,28.11Z"
                                fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="16"></path>
                        </svg> <!-- End::header-link-icon --> </span> <span class="dark-layout">
                        <!-- Start::header-link-icon --> <svg xmlns="http://www.w3.org/2000/svg"
                            class="header-link-icon" viewBox="0 0 256 256">
                            <rect width="256" height="256" fill="none"></rect>
                            <line x1="128" y1="40" x2="128" y2="32" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16">
                            </line>
                            <circle cx="128" cy="128" r="56" fill="none" stroke="currentColor"
                                stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></circle>
                            <line x1="64" y1="64" x2="56" y2="56" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16">
                            </line>
                            <line x1="64" y1="192" x2="56" y2="200" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16">
                            </line>
                            <line x1="192" y1="64" x2="200" y2="56" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="16">
                            </line>
                            <line x1="192" y1="192" x2="200" y2="200" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="16"></line>
                            <line x1="40" y1="128" x2="32" y2="128" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="16"></line>
                            <line x1="128" y1="216" x2="128" y2="224" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="16"></line>
                            <line x1="216" y1="128" x2="224" y2="128" fill="none"
                                stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="16"></line>
                        </svg> <!-- End::header-link-icon --> </span> </a> <!-- End::header-link|layout-setting -->
            </li>

            <!-- End::header-element --> <!-- Start::header-element -->
            <li class="header-element notifications-dropdown d-xl-block d-none dropdown">
                <!-- Start::header-link|dropdown-toggle --> <a href="javascript:void(0);"
                    class="header-link dropdown-toggle" data-bs-toggle="dropdown" data-bs-auto-close="outside"
                    id="messageDropdown" aria-expanded="false"> <svg xmlns="http://www.w3.org/2000/svg"
                        class="header-link-icon animate-bell" viewBox="0 0 256 256">
                        <rect width="256" height="256" fill="none"></rect>
                        <path d="M96,192a32,32,0,0,0,64,0" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path>
                        <path d="M184,24a102.71,102.71,0,0,1,36.29,40" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path>
                        <path d="M35.71,64A102.71,102.71,0,0,1,72,24" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></path>
                        <path
                            d="M56,112a72,72,0,0,1,144,0c0,35.82,8.3,56.6,14.9,68A8,8,0,0,1,208,192H48a8,8,0,0,1-6.88-12C47.71,168.6,56,147.81,56,112Z"
                            fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="16"></path>
                    </svg>
                    <span class="header-icon-pulse bg-secondary rounded pulse pulse-secondary"></span>
                </a>
                <!-- End::header-link|dropdown-toggle --> <!-- Start::main-header-dropdown -->
                <div class="main-header-dropdown dropdown-menu dropdown-menu-end" data-popper-placement="none">
                    <div class="p-3">
                        {{-- <div class="d-flex align-items-center justify-content-between">
                                <p class="mb-0 fs-16">{{ __('Notifications') }}</p>
                                    <span class="badge bg-secondary-transparent">{{ $headerUnreadCount }}
                                        {{ __('Unread') }}</span>
                            </div> --}}
                    </div>
                    <div class="dropdown-divider"></div>
                    <div class="px-3 pb-2">
                        {{-- @if ($headerUnreadCount > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST"
                                    class="mb-2">
                                    @csrf
                                    <button type="submit"
                                        class="btn btn-sm btn-outline-secondary w-100">{{ __('Mark all as read') }}</button>
                                </form>
                            @endif --}}
                        <ul class="list-unstyled mb-0" id="header-notification-scroll1" data-simplebar="init">
                            <div class="simplebar-wrapper" style="margin: 0px;">
                                <div class="simplebar-height-auto-observer-wrapper">
                                    <div class="simplebar-height-auto-observer"></div>
                                </div>
                                <div class="simplebar-mask">
                                    <div class="simplebar-offset" style="right: 0px; bottom: 0px;">
                                        <div class="simplebar-content-wrapper" tabindex="0" role="region"
                                            aria-label="scrollable content" style="height: auto; overflow: hidden;">
                                            <div class="simplebar-content" style="padding: 0px;">

                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="simplebar-placeholder" style="width: 0px; height: 0px;"></div>
                            </div>
                            <div class="simplebar-track simplebar-horizontal" style="visibility: hidden;">
                                <div class="simplebar-scrollbar" style="width: 0px; display: none;"></div>
                            </div>
                            <div class="simplebar-track simplebar-vertical" style="visibility: hidden;">
                                <div class="simplebar-scrollbar" style="height: 0px; display: none;"></div>
                            </div>
                        </ul>
                    </div>
                    <div class="p-3 empty-header-item1 border-top">
                        <div class="d-grid text-center">
                            {{-- <a href="{{ route('notifications.index') }}"
                                class="text-primary text-decoration-underline">
                                {{ __('View All') }}<i class="ri-arrow-right-line"></i>
                            </a> --}}
                        </div>
                    </div>
                </div> <!-- End::main-header-dropdown -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element header-fullscreen">
                <!-- Start::header-link -->
                <a href="javascript:void(0);" class="header-link js-fullscreen-toggle"> <svg
                        xmlns="http://www.w3.org/2000/svg" class="full-screen-open header-link-icon"
                        viewBox="0 0 256 256">
                        <rect width="256" height="256" fill="none"></rect>
                        <polyline points="168 48 208 48 208 88" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline>
                        <polyline points="88 208 48 208 48 168" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline>
                        <polyline points="208 168 208 208 168 208" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline>
                        <polyline points="48 88 48 48 88 48" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline>
                    </svg> <svg xmlns="http://www.w3.org/2000/svg" class="full-screen-close header-link-icon d-none"
                        viewBox="0 0 256 256">
                        <rect width="256" height="256" fill="none"></rect>
                        <polyline points="160 48 208 48 208 96" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline>
                        <line x1="144" y1="112" x2="208" y2="48" fill="none"
                            stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16">
                        </line>
                        <polyline points="96 208 48 208 48 160" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></polyline>
                        <line x1="112" y1="144" x2="48" y2="208" fill="none"
                            stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="16">
                        </line>
                    </svg> </a> <!-- End::header-link -->
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element dropdown">
                <!-- Start::header-link|dropdown-toggle -->
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="me-xl-2 me-0"> <img src="../assets/images/faces/2.jpg" alt="img"
                                class="avatar avatar-sm avatar-rounded"> </div>
                        <div class="d-xl-block d-none lh-1"> <span class="fw-medium lh-1">{{ 'TEST' }}</span>
                        </div>
                    </div>
                </a> <!-- End::header-link|dropdown-toggle -->
                <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end"
                    aria-labelledby="mainHeaderProfile">
                    <li>
                        <div class="py-2 px-3 text-center"> <span class="fw-semibold"> {{ 'TEST' }}
                            </span> <span class="d-block fs-12 text-muted">{{ $headerRole ?? __('User') }}</span>
                        </div>
                    </li>
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('tenant.profile.show') }}"><i
                                class="ti ti-user text-primary me-2 fs-16"></i>{{ __('Profile') }}</a> </li>
                    <li><a class="dropdown-item d-flex align-items-center" href="{{ route('tenant.settings.edit') }}"><i
                                class="ti ti-settings text-info me-2 fs-16"></i>{{ __('Settings') }}</a> </li>
                    <li><a class="dropdown-item d-flex align-items-center" href="chat.html"><i
                                class="ti ti-headset text-warning me-2 fs-16"></i>{{ __('Support') }}</a> </li>
                    <li class="py-2 px-3">
                        <form action="{{ route('tenant.logout', ['tenant' => tenant('id')]) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                {{ __('Log Out') }}
                            </button>
                        </form>
                    </li>

                </ul>
            </li>
            <!-- End::header-element -->

            <!-- Start::header-element -->
            <li class="header-element">
                <!-- Start::header-link|switcher-icon -->
                <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas"
                    data-bs-target="#switcher-canvas">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon" viewBox="0 0 256 256">
                        <rect width="256" height="256" fill="none"></rect>
                        <circle cx="128" cy="128" r="40" fill="none" stroke="currentColor"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="16"></circle>
                        <path
                            d="M41.43,178.09A99.14,99.14,0,0,1,31.36,153.8l16.78-21a81.59,81.59,0,0,1,0-9.64l-16.77-21a99.43,99.43,0,0,1,10.05-24.3l26.71-3a81,81,0,0,1,6.81-6.81l3-26.7A99.14,99.14,0,0,1,102.2,31.36l21,16.78a81.59,81.59,0,0,1,9.64,0l21-16.77a99.43,99.43,0,0,1,24.3,10.05l3,26.71a81,81,0,0,1,6.81,6.81l26.7,3a99.14,99.14,0,0,1,10.07,24.29l-16.78,21a81.59,81.59,0,0,1,0,9.64l16.77,21a99.43,99.43,0,0,1-10,24.3l-26.71,3a81,81,0,0,1-6.81,6.81l-3,26.7a99.14,99.14,0,0,1-24.29,10.07l-21-16.78a81.59,81.59,0,0,1-9.64,0l-21,16.77a99.43,99.43,0,0,1-24.3-10l-3-26.71a81,81,0,0,1-6.81-6.81Z"
                            fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                            stroke-width="16"></path>
                    </svg> </a> <!-- End::header-link|switcher-icon -->
            </li>
            <!-- End::header-element -->

        </ul> <!-- End::header-content-right -->
    </div> <!-- End::main-header-container -->
</header>
<script>
    document.addEventListener('DOMContentLoaded', () => {

    });
</script>
