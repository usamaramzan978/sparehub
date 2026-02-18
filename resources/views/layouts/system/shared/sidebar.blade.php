<aside class="app-sidebar sticky" id="sidebar">
    <div class="main-sidebar-header">
        <a href="{{ route('system.dashboard') }}" class="header-logo">
            <img src="../assets/images/brand-logos/desktop-logo.png" alt="logo" class="desktop-logo">
        </a>
    </div>

    <div class="main-sidebar" id="sidebar-scroll" data-simplebar="init">
        <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="sidebar">
            <div class="simplebar-content">
                @php
                    $isDashboard = request()->routeIs('system.dashboard');
                    $isTenants = request()->routeIs('system.tenants.*');
                    $isPlans = request()->routeIs('system.plans.*');
                    $isManagement = $isTenants || $isPlans;
                @endphp

                <nav class="main-menu-container nav nav-pills flex-column">
                    <ul class="main-menu">
                        <li class="slide__category"><span class="category-name">{{ __('System CRM') }}</span></li>

                        <li class="slide {{ $isDashboard ? 'active' : '' }}">
                            <a href="{{ route('system.dashboard') }}" class="side-menu__item {{ $isDashboard ? 'active' : '' }}">
                                <i class="ri-dashboard-line side-menu__icon"></i>
                                <span class="side-menu__label">{{ __('Dashboard') }}</span>
                            </a>
                        </li>

                        <li class="slide has-sub {{ $isManagement ? 'active open' : '' }}">
                            <a href="javascript:void(0);" class="side-menu__item {{ $isManagement ? 'active' : '' }}">
                                <i class="ri-settings-3-line side-menu__icon"></i>
                                <span class="side-menu__label">{{ __('Management') }}</span>
                                <i class="ri-arrow-right-s-line side-menu__angle"></i>
                            </a>
                            <ul class="slide-menu child1 {{ $isManagement ? 'open' : '' }}">
                                <li class="slide {{ $isTenants ? 'active' : '' }}">
                                    <a href="{{ route('system.tenants.index') }}" class="side-menu__item {{ $isTenants ? 'active' : '' }}">
                                        {{ __('Tenants') }}
                                    </a>
                                </li>
                                <li class="slide {{ $isPlans ? 'active' : '' }}">
                                    <a href="{{ route('system.plans.index') }}" class="side-menu__item {{ $isPlans ? 'active' : '' }}">
                                        {{ __('Plans') }}
                                    </a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</aside>
