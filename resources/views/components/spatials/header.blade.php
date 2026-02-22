<!-- ========== Topbar Start ========== -->
<div class="navbar-custom">
    <div class="topbar container-fluid">
        <div class="d-flex align-items-center gap-1">

            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo light -->
                <a href="{{ route('dashboard') }}" class="logo-light">
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/logo.png') }}" alt="logo">
                    </span>
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo">
                    </span>
                </a>

                <!-- Logo Dark -->
                <a href="{{ route('dashboard') }}" class="logo-dark">
                    <span class="logo-lg">
                        <img src="{{ asset('assets/images/logo-dark.png') }}" alt="dark logo">
                    </span>
                    <span class="logo-sm">
                        <img src="{{ asset('assets/images/logo-sm.png') }}" alt="small logo">
                    </span>
                </a>
            </div>

            <!-- Sidebar Menu Toggle Button -->
            <button class="button-toggle-menu">
                <i class="ri-menu-line"></i>
            </button>

            <!-- Horizontal Menu Toggle Button -->
            <button class="navbar-toggle" data-bs-toggle="collapse" data-bs-target="#topnav-menu-content">
                <div class="lines">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </button>

            <!-- Topbar Search Form -->
            <div class="app-search d-none d-lg-block">
                <form>
                    <div class="input-group">
                        <input type="search" class="form-control" placeholder="Cari...">
                        <span class="ri-search-line search-icon text-muted"></span>
                    </div>
                </form>
            </div>
        </div>

        <ul class="topbar-menu d-flex align-items-center gap-3">
            <li class="dropdown d-lg-none">
                <a class="nav-link dropdown-toggle arrow-none" data-bs-toggle="dropdown" href="#" role="button"
                    aria-haspopup="false" aria-expanded="false">
                    <i class="ri-search-line fs-22"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-animated dropdown-lg p-0">
                    <form class="p-3">
                        <input type="search" class="form-control" placeholder="Cari..."
                            aria-label="Cari">
                    </form>
                </div>
            </li>

            @if(auth()->user()?->businessUnit)
            <li class="d-none d-sm-inline-block">
                <span class="nav-link text-muted" title="Unit Usaha Aktif">
                    <i class="ri-store-2-line me-1"></i>
                    <span class="d-none d-lg-inline">{{ auth()->user()->businessUnit->name }}</span>
                </span>
            </li>
            @endif

            <li class="d-none d-sm-inline-block">
                <a class="nav-link" data-bs-toggle="offcanvas" href="#theme-settings-offcanvas">
                    <i class="ri-settings-3-line fs-22"></i>
                </a>
            </li>

            <li class="d-none d-sm-inline-block">
                <div class="nav-link" id="light-dark-mode">
                    <i class="ri-moon-line fs-22"></i>
                </div>
            </li>

            <li class="dropdown">
                <a class="nav-link dropdown-toggle arrow-none nav-user" data-bs-toggle="dropdown" href="#" role="button"
                    aria-haspopup="false" aria-expanded="false">
                    <span class="account-user-avatar">
                        @if(auth()->user()?->avatar)
                            <img src="{{ auth()->user()->avatar }}" alt="user-image" width="32" class="rounded-circle">
                        @else
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 14px;">
                                {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                            </div>
                        @endif
                    </span>
                    <span class="d-lg-block d-none">
                        <h5 class="my-0 fw-normal">{{ auth()->user()?->name ?? 'User' }} <i
                                class="ri-arrow-down-s-line d-none d-sm-inline-block align-middle"></i></h5>
                    </span>
                </a>
                <div class="dropdown-menu dropdown-menu-end dropdown-menu-animated profile-dropdown">
                    <!-- item-->
                    <div class="dropdown-header noti-title">
                        <h6 class="text-overflow m-0">Selamat Datang!</h6>
                    </div>

                    <div class="dropdown-item text-muted small" style="pointer-events: none;">
                        <i class="ri-at-line fs-16 align-middle me-1"></i>
                        <span>{{ auth()->user()?->username ?? '-' }}</span>
                    </div>

                    @if(auth()->user()?->roles->isNotEmpty())
                    <div class="dropdown-item text-muted small" style="pointer-events: none;">
                        <i class="ri-shield-user-line fs-16 align-middle me-1"></i>
                        <span>{{ auth()->user()->roles->pluck('name')->implode(', ') }}</span>
                    </div>
                    @endif

                    <div class="dropdown-divider"></div>

                    <!-- item-->
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button type="submit" class="dropdown-item border-0">
                            <i class="ri-logout-box-line fs-18 align-middle me-1"></i>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</div>
<!-- ========== Topbar End ========== -->