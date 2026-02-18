<aside class="app-sidebar sticky" id="sidebar">
    @php
        $tenantSettings = \App\Models\TenantSetting::query()->first();
        $tenantLogo = $tenantSettings?->logo_path
            ? asset('storage/' . $tenantSettings->logo_path)
            : '../assets/images/brand-logos/desktop-logo.png';
    @endphp

    <div class="main-sidebar-header">
        <a href="{{ route('tenant.dashboard') }}" class="header-logo">
            <img src="{{ $tenantLogo }}" alt="logo" class="desktop-logo">
        </a>
    </div>

    <div class="main-sidebar" id="sidebar-scroll" data-simplebar="init">
        <div class="simplebar-wrapper">
            <div class="simplebar-mask">
                <div class="simplebar-offset">
                    <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="sidebar">
                        <div class="simplebar-content">
                            <nav class="main-menu-container nav nav-pills flex-column">
                                @php
                                    $tenantKey = (string) tenant('id');

                                    $isDashboard = request()->routeIs('tenant.dashboard', 'tenant.end-of-day');
                                    $isEndOfDay = request()->routeIs('tenant.end-of-day');

                                    $isMasterData = request()->routeIs(
                                        'tenant.branches.*',
                                        'tenant.units.*',
                                        'tenant.taxes.*',
                                        'tenant.categories.*',
                                        'tenant.brands.*',
                                        'tenant.products.*',
                                        'tenant.product-prices.*',
                                        'tenant.service-catalog.*',
                                        'tenant.customers.*',
                                        'tenant.customer-vehicles.*',
                                        'tenant.vendors.*',
                                    );
                                    $isBranches = request()->routeIs('tenant.branches.*');
                                    $isUnits = request()->routeIs('tenant.units.*');
                                    $isTaxes = request()->routeIs('tenant.taxes.*');
                                    $isCategories = request()->routeIs('tenant.categories.*');
                                    $isBrands = request()->routeIs('tenant.brands.*');
                                    $isProducts = request()->routeIs('tenant.products.*');
                                    $isProductPrices = request()->routeIs('tenant.product-prices.*');
                                    $isServiceCatalog = request()->routeIs('tenant.service-catalog.*');
                                    $isCustomers = request()->routeIs('tenant.customers.*');
                                    $isCustomerVehicles = request()->routeIs('tenant.customer-vehicles.*');
                                    $isVendors = request()->routeIs('tenant.vendors.*');

                                    $isWorkshop = request()->routeIs(
                                        'tenant.job-cards.*',
                                        'tenant.job-card-services.*',
                                        'tenant.job-card-parts.*',
                                    );
                                    $isJobCards = request()->routeIs('tenant.job-cards.*');
                                    $isJobCardServices = request()->routeIs('tenant.job-card-services.*');
                                    $isJobCardParts = request()->routeIs('tenant.job-card-parts.*');

                                    $isSales = request()->routeIs(
                                        'tenant.pos.*',
                                        'tenant.sales.*',
                                        'tenant.sale-items.*',
                                        'tenant.sale-payments.*',
                                        'tenant.sale-holds.*',
                                    );
                                    $isPos = request()->routeIs('tenant.pos.*');
                                    $isSalesInvoices = request()->routeIs('tenant.sales.*');
                                    $isSaleItems = request()->routeIs('tenant.sale-items.*');
                                    $isSalePayments = request()->routeIs('tenant.sale-payments.*');
                                    $isSaleHolds = request()->routeIs('tenant.sale-holds.*');

                                    $isPurchases = request()->routeIs(
                                        'tenant.purchases.*',
                                        'tenant.purchase-items.*',
                                        'tenant.purchase-returns.*',
                                        'tenant.purchase-return-items.*',
                                        'tenant.vendor-payments.*',
                                    );
                                    $isPurchaseInvoices = request()->routeIs('tenant.purchases.*');
                                    $isPurchaseItems = request()->routeIs('tenant.purchase-items.*');
                                    $isPurchaseReturns = request()->routeIs('tenant.purchase-returns.*');
                                    $isPurchaseReturnItems = request()->routeIs('tenant.purchase-return-items.*');
                                    $isVendorPayments = request()->routeIs('tenant.vendor-payments.*');
                                    $isInventory = request()->routeIs('tenant.inventory.*');

                                    $isAccessControl = request()->routeIs(
                                        'tenant.users.*',
                                        'tenant.roles.*',
                                        'tenant.permissions.*',
                                    );
                                    $isEmployees = request()->routeIs(
                                        'tenant.employee-attendances.*',
                                        'tenant.employee-salaries.*',
                                    );
                                    $isEmployeeAttendances = request()->routeIs('tenant.employee-attendances.*');
                                    $isEmployeeSalaries = request()->routeIs('tenant.employee-salaries.*');
                                    $isUsers = request()->routeIs('tenant.users.*');
                                    $isRoles = request()->routeIs('tenant.roles.*');
                                    $isPermissions = request()->routeIs('tenant.permissions.*');
                                    $isSystem = request()->routeIs('tenant.reports.*', 'tenant.settings.*');
                                    $isReports = request()->routeIs('tenant.reports.*');
                                    $isSettings = request()->routeIs('tenant.settings.*');
                                    $isProfile = request()->routeIs('tenant.profile.*');
                                @endphp

                                <ul class="main-menu">
                                    <li class="slide__category"><span class="category-name">{{ __('Dashboard') }}</span>
                                    </li>
                                    <li class="slide {{ $isDashboard ? 'active' : '' }}">
                                        <a href="{{ route('tenant.dashboard') }}"
                                            class="side-menu__item {{ $isDashboard ? 'active' : '' }}">
                                            <i class="ri-dashboard-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Overview') }}</span>
                                        </a>
                                    </li>
                                    <li class="slide {{ $isEndOfDay ? 'active' : '' }}">
                                        <a href="{{ route('tenant.end-of-day') }}"
                                            class="side-menu__item {{ $isEndOfDay ? 'active' : '' }}">
                                            <i class="ri-file-chart-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('End Of Day') }}</span>
                                        </a>
                                    </li>

                                    <li class="slide has-sub {{ $isMasterData ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isMasterData ? 'active' : '' }}">
                                            <i class="ri-book-2-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Master Data') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isMasterData ? 'open' : '' }}">
                                            <li class="slide has-sub {{ $isBranches ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isBranches ? 'active' : '' }}">
                                                    {{ __('Branches') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isBranches ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.branches.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.branches.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.branches.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.branches.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.branches.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.branches.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>

                                            <li class="slide"><a href="#"
                                                    class="side-menu__item">{{ __('Warehouses') }}</a></li>

                                            <li class="slide {{ $isUnits ? 'active' : '' }}">
                                                <a href="{{ route('tenant.units.index') }}"
                                                    class="side-menu__item {{ $isUnits ? 'active' : '' }}">{{ __('Units') }}</a>
                                            </li>
                                            <li class="slide {{ $isTaxes ? 'active' : '' }}">
                                                <a href="{{ route('tenant.taxes.index') }}"
                                                    class="side-menu__item {{ $isTaxes ? 'active' : '' }}">{{ __('Taxes') }}</a>
                                            </li>
                                            <li class="slide {{ $isCategories ? 'active' : '' }}">
                                                <a href="{{ route('tenant.categories.index') }}"
                                                    class="side-menu__item {{ $isCategories ? 'active' : '' }}">{{ __('Categories') }}</a>
                                            </li>
                                            <li class="slide {{ $isBrands ? 'active' : '' }}">
                                                <a href="{{ route('tenant.brands.index') }}"
                                                    class="side-menu__item {{ $isBrands ? 'active' : '' }}">{{ __('Brands') }}</a>
                                            </li>

                                            <li class="slide has-sub {{ $isProducts ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isProducts ? 'active' : '' }}">
                                                    {{ __('Products') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isProducts ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.products.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.products.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.products.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.products.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.products.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.products.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>

                                            <li class="slide {{ $isProductPrices ? 'active' : '' }}">
                                                <a href="{{ route('tenant.product-prices.index') }}"
                                                    class="side-menu__item {{ $isProductPrices ? 'active' : '' }}">{{ __('Product Prices') }}</a>
                                            </li>
                                            <li class="slide {{ $isServiceCatalog ? 'active' : '' }}">
                                                <a href="{{ route('tenant.service-catalog.index') }}"
                                                    class="side-menu__item {{ $isServiceCatalog ? 'active' : '' }}">{{ __('Service Catalog') }}</a>
                                            </li>

                                            <li class="slide has-sub {{ $isCustomers ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isCustomers ? 'active' : '' }}">
                                                    {{ __('Customers') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isCustomers ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.customers.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.customers.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.customers.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.customers.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.customers.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.customers.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>

                                            <li class="slide {{ $isCustomerVehicles ? 'active' : '' }}">
                                                <a href="{{ route('tenant.customer-vehicles.index') }}"
                                                    class="side-menu__item {{ $isCustomerVehicles ? 'active' : '' }}">{{ __('Customer Vehicles') }}</a>
                                            </li>
                                            <li class="slide {{ $isVendors ? 'active' : '' }}">
                                                <a href="{{ route('tenant.vendors.index') }}"
                                                    class="side-menu__item {{ $isVendors ? 'active' : '' }}">{{ __('Vendors') }}</a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="slide has-sub {{ $isWorkshop ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isWorkshop ? 'active' : '' }}">
                                            <i class="ri-tools-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Workshop') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isWorkshop ? 'open' : '' }}">
                                            <li class="slide has-sub {{ $isJobCards ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isJobCards ? 'active' : '' }}">
                                                    {{ __('Job Cards') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isJobCards ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.job-cards.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.job-cards.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.job-cards.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.job-cards.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.job-cards.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.job-cards.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isJobCardServices ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isJobCardServices ? 'active' : '' }}">
                                                    {{ __('Job Card Services') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isJobCardServices ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.job-card-services.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.job-card-services.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.job-card-services.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.job-card-services.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.job-card-services.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.job-card-services.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isJobCardParts ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isJobCardParts ? 'active' : '' }}">
                                                    {{ __('Job Card Parts') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isJobCardParts ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.job-card-parts.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.job-card-parts.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.job-card-parts.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.job-card-parts.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.job-card-parts.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.job-card-parts.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="slide has-sub {{ $isSales ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isSales ? 'active' : '' }}">
                                            <i class="ri-shopping-cart-2-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Sales') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isSales ? 'open' : '' }}">
                                            <li class="slide {{ $isPos ? 'active' : '' }}">
                                                <a href="{{ route('tenant.pos.index') }}"
                                                    class="side-menu__item {{ $isPos ? 'active' : '' }}">{{ __('POS') }}</a>
                                            </li>
                                            <li class="slide has-sub {{ $isSalesInvoices ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isSalesInvoices ? 'active' : '' }}">
                                                    {{ __('Sales Invoices') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isSalesInvoices ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.sales.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.sales.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.sales.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.sales.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.sales.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.sales.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isSaleItems ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isSaleItems ? 'active' : '' }}">
                                                    {{ __('Sale Items') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isSaleItems ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.sale-items.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.sale-items.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.sale-items.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.sale-items.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.sale-items.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.sale-items.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isSalePayments ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isSalePayments ? 'active' : '' }}">
                                                    {{ __('Sale Payments') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isSalePayments ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.sale-payments.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.sale-payments.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.sale-payments.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.sale-payments.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.sale-payments.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.sale-payments.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide {{ $isSaleHolds ? 'active' : '' }}">
                                                <a href="{{ route('tenant.sale-holds.index') }}"
                                                    class="side-menu__item {{ $isSaleHolds ? 'active' : '' }}">{{ __('Sale Holds (POS Hold)') }}</a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="slide has-sub {{ $isPurchases ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isPurchases ? 'active' : '' }}">
                                            <i class="ri-shopping-bag-3-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Purchases') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isPurchases ? 'open' : '' }}">
                                            <li class="slide has-sub {{ $isPurchaseInvoices ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isPurchaseInvoices ? 'active' : '' }}">
                                                    {{ __('Purchases') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul
                                                    class="slide-menu child2 {{ $isPurchaseInvoices ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchases.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchases.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchases.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchases.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchases.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchases.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isPurchaseItems ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isPurchaseItems ? 'active' : '' }}">
                                                    {{ __('Purchase Items') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isPurchaseItems ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchase-items.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchase-items.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchase-items.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchase-items.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchase-items.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchase-items.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isPurchaseReturns ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isPurchaseReturns ? 'active' : '' }}">
                                                    {{ __('Purchase Returns') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isPurchaseReturns ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchase-returns.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchase-returns.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchase-returns.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchase-returns.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchase-returns.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchase-returns.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li
                                                class="slide has-sub {{ $isPurchaseReturnItems ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isPurchaseReturnItems ? 'active' : '' }}">
                                                    {{ __('Purchase Return Items') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul
                                                    class="slide-menu child2 {{ $isPurchaseReturnItems ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchase-return-items.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchase-return-items.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchase-return-items.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.purchase-return-items.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.purchase-return-items.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.purchase-return-items.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide has-sub {{ $isVendorPayments ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isVendorPayments ? 'active' : '' }}">
                                                    {{ __('Vendor Payments') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isVendorPayments ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.vendor-payments.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.vendor-payments.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.vendor-payments.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.vendor-payments.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.vendor-payments.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.vendor-payments.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="slide has-sub {{ $isInventory ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isInventory ? 'active' : '' }}">
                                            <i class="ri-stack-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Inventory') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isInventory ? 'open' : '' }}">
                                            <li class="slide"><a href="#"
                                                    class="side-menu__item">{{ __('Inventory Stocks') }}</a></li>
                                            <li class="slide"><a href="#"
                                                    class="side-menu__item">{{ __('Stock Moves') }}</a></li>
                                        </ul>
                                    </li>

                                    {{-- <li class="slide has-sub {{ $isAccessControl ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isAccessControl ? 'active' : '' }}">
                                            <i class="ri-shield-user-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Access Control') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isAccessControl ? 'open' : '' }}">
                                            <li class="slide has-sub {{ $isUsers ? 'active open' : '' }}">
                                                <a href="javascript:void(0);"
                                                    class="side-menu__item {{ $isUsers ? 'active' : '' }}">
                                                    {{ __('Users') }}
                                                    <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                                </a>
                                                <ul class="slide-menu child2 {{ $isUsers ? 'open' : '' }}">
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.users.index') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.users.index') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.users.index') ? 'active' : '' }}">{{ __('List') }}</a>
                                                    </li>
                                                    <li
                                                        class="slide {{ request()->routeIs('tenant.users.create') ? 'active' : '' }}">
                                                        <a href="{{ route('tenant.users.create') }}"
                                                            class="side-menu__item {{ request()->routeIs('tenant.users.create') ? 'active' : '' }}">{{ __('Create') }}</a>
                                                    </li>
                                                </ul>
                                            </li>
                                            <li class="slide {{ $isRoles ? 'active' : '' }}">
                                                <a href="{{ route('tenant.roles.index') }}"
                                                    class="side-menu__item {{ $isRoles ? 'active' : '' }}">
                                                    {{ __('Roles') }}
                                                </a>
                                            </li>
                                            <li class="slide {{ $isPermissions ? 'active' : '' }}">
                                                <a href="{{ route('tenant.permissions.index') }}"
                                                    class="side-menu__item {{ $isPermissions ? 'active' : '' }}">
                                                    {{ __('Permissions') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li> --}}

                                    <li class="slide has-sub {{ $isEmployees ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isEmployees ? 'active' : '' }}">
                                            <i class="ri-team-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Employees') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isEmployees ? 'open' : '' }}">
                                            <li class="slide {{ $isEmployeeAttendances ? 'active' : '' }}">
                                                <a href="{{ route('tenant.employee-attendances.index') }}"
                                                    class="side-menu__item {{ $isEmployeeAttendances ? 'active' : '' }}">
                                                    {{ __('Attendance') }}
                                                </a>
                                            </li>
                                            <li class="slide {{ $isEmployeeSalaries ? 'active' : '' }}">
                                                <a href="{{ route('tenant.employee-salaries.index') }}"
                                                    class="side-menu__item {{ $isEmployeeSalaries ? 'active' : '' }}">
                                                    {{ __('Salaries') }}
                                                </a>
                                            </li>
                                        </ul>
                                    </li>

                                    <li class="slide has-sub {{ $isSystem ? 'active open' : '' }}">
                                        <a href="javascript:void(0);"
                                            class="side-menu__item {{ $isSystem ? 'active' : '' }}">
                                            <i class="ri-settings-3-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('System') }}</span>
                                            <i class="ri-arrow-right-s-line side-menu__angle"></i>
                                        </a>
                                        <ul class="slide-menu child1 {{ $isSystem ? 'open' : '' }}">
                                            <li class="slide {{ $isSettings ? 'active' : '' }}">
                                                <a href="{{ route('tenant.settings.edit') }}"
                                                    class="side-menu__item {{ $isSettings ? 'active' : '' }}">{{ __('Settings') }}</a>
                                            </li>
                                            <li class="slide {{ $isReports ? 'active' : '' }}">
                                                <a href="{{ route('tenant.reports.index') }}"
                                                    class="side-menu__item {{ $isReports ? 'active' : '' }}">{{ __('Reports') }}</a>
                                            </li>
                                            <li class="slide"><a href="#"
                                                    class="side-menu__item">{{ __('Audit Logs') }}</a></li>
                                            <li class="slide"><a href="#"
                                                    class="side-menu__item">{{ __('Reminders') }}</a></li>
                                        </ul>
                                    </li>

                                    <li class="slide__category"><span
                                            class="category-name">{{ __('Account') }}</span></li>
                                    <li class="slide {{ $isProfile ? 'active' : '' }}">
                                        <a href="{{ route('tenant.profile.show') }}"
                                            class="side-menu__item {{ $isProfile ? 'active' : '' }}">
                                            <i class="ri-user-line side-menu__icon"></i>
                                            <span class="side-menu__label">{{ __('Profile') }}</span>
                                        </a>
                                    </li>
                                    <li class="slide">
                                        <form method="POST" action="{{ route('tenant.logout') }}">
                                            @csrf
                                            <button type="submit" class="side-menu__item btn w-100 text-start">
                                                <i class="ri-logout-box-line side-menu__icon"></i>
                                                <span class="side-menu__label">{{ __('Logout') }}</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</aside>
