<header class="app-header sticky" id="header">
    <div class="main-header-container container-fluid">
        <div class="header-content-left">
            <div class="header-element">
                <div class="horizontal-logo">
                    <a href="{{ route('system.dashboard') }}" class="header-logo">
                        <img src="../assets/images/brand-logos/toggle-dark.png" alt="logo" class="desktop-logo">
                    </a>
                </div>
            </div>

            <div class="header-element">
                <a aria-label="{{ __('Hide Sidebar') }}" class="sidemenu-toggle header-link" data-bs-toggle="sidebar"
                    href="javascript:void(0);">
                    <svg xmlns="http://www.w3.org/2000/svg" class="header-link-icon menu-btn" width="32" height="32"
                        fill="#000000" viewBox="0 0 256 256">
                        <path
                            d="M224,128a8,8,0,0,1-8,8H40a8,8,0,0,1,0-16H216A8,8,0,0,1,224,128ZM40,72H216a8,8,0,0,0,0-16H40a8,8,0,0,0,0,16ZM216,184H40a8,8,0,0,0,0,16H216a8,8,0,0,0,0-16Z">
                        </path>
                    </svg>
                </a>
            </div>
        </div>

        <ul class="header-content-right">
            <li class="header-element header-theme-mode">
                <a href="javascript:void(0);" class="header-link layout-setting">
                    <span class="light-layout"><i class="ri-moon-line header-link-icon"></i></span>
                    <span class="dark-layout"><i class="ri-sun-line header-link-icon"></i></span>
                </a>
            </li>

            <li class="header-element header-fullscreen">
                <a href="javascript:void(0);" class="header-link js-fullscreen-toggle">
                    <i class="ri-fullscreen-line header-link-icon full-screen-open"></i>
                    <i class="ri-fullscreen-exit-line header-link-icon full-screen-close d-none"></i>
                </a>
            </li>

            <li class="header-element dropdown">
                <a href="javascript:void(0);" class="header-link dropdown-toggle" id="mainHeaderProfile"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                    <div class="d-flex align-items-center">
                        <div class="me-xl-2 me-0">
                            <img src="../assets/images/faces/2.jpg" alt="img" class="avatar avatar-sm avatar-rounded">
                        </div>
                        <div class="d-xl-block d-none lh-1">
                            <span class="fw-medium lh-1">{{ auth('system')->user()?->name ?? 'System Owner' }}</span>
                        </div>
                    </div>
                </a>

                <ul class="main-header-dropdown dropdown-menu pt-0 overflow-hidden header-profile-dropdown dropdown-menu-end"
                    aria-labelledby="mainHeaderProfile">
                    <li>
                        <div class="py-2 px-3 text-center">
                            <span class="fw-semibold">{{ auth('system')->user()?->name ?? 'System Owner' }}</span>
                            <span class="d-block fs-12 text-muted">{{ auth('system')->user()?->email }}</span>
                        </div>
                    </li>
                    <li class="py-2 px-3">
                        <form action="{{ route('system.logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-sm w-100">{{ __('Log Out') }}</button>
                        </form>
                    </li>
                </ul>
            </li>

            <li class="header-element">
                <a href="javascript:void(0);" class="header-link switcher-icon" data-bs-toggle="offcanvas"
                    data-bs-target="#switcher-canvas">
                    <i class="ri-settings-3-line header-link-icon"></i>
                </a>
            </li>
        </ul>
    </div>
</header>
