<!-- ========== Left Sidebar Start ========== -->
<div class="leftside-menu">

    <!-- Brand Logo Light -->
    <a href="index.html" class="logo logo-light">
        <span class="logo-lg">
            <img src="assets/images/logo.png" alt="logo">
        </span>
        <span class="logo-sm">
            <img src="assets/images/logo-sm.png" alt="small logo">
        </span>
    </a>

    <!-- Brand Logo Dark -->
    <a href="index.html" class="logo logo-dark">
        <span class="logo-lg">
            <img src="assets/images/logo-dark.png" alt="dark logo">
        </span>
        <span class="logo-sm">
            <img src="assets/images/logo-sm.png" alt="small logo">
        </span>
    </a>

    <!-- Sidebar -left -->
    <div class="h-100" id="leftside-menu-container" data-simplebar>
        <!--- Sidemenu -->
        <ul class="side-nav">

            <li class="side-nav-title">Main</li>

            <li class="side-nav-item">
                <a href="javascript:" class="side-nav-link">
                    {{-- <a href="/" class="side-nav-link"> --}}
                        <i class="ri-dashboard-3-line"></i>
                        <span class="badge bg-success float-end">9+</span>
                        <span> Dashboard </span>
                    </a>
            </li>

            <li class="side-nav-title">Master Data</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPages" aria-expanded="false" aria-controls="sidebarPages"
                    class="side-nav-link">
                    <i class="ri-database-2-line"></i>
                    <span> Perusahaan & Sistem </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPages">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('company.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('company.index') }}"> --}}
                                    <i class="ri-building-line me-1"></i> Perusahaan
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('coa.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('coa.index') }}"> --}}
                                    <i class="ri-file-list-line me-1"></i> Chart of Accounts
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('permission.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('permission.index') }}"> --}}
                                    <i class="ri-key-2-line me-1"></i> Permission
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('role.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('role.index') }}"> --}}
                                    <i class="ri-shield-user-line me-1"></i> Role
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('user.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('user.index') }}"> --}}
                                    <i class="ri-user-line me-1"></i> User
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarConfigAccounting" aria-expanded="false"
                    aria-controls="sidebarConfigAccounting" class="side-nav-link">
                    <i class="ri-settings-5-line"></i>
                    <span> Config Ankuntansi </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarConfigAccounting">
                    <ul class="side-nav-second-level">
                        <li class="">

                        <li class="{{ request()->routeIs('coa') ? 'active' : '' }}">
                            <a href="{{ route('coa') }}">
                                <i class="ri-file-list-line me-1"></i> Chart of Accounts
                            </a>
                        </li>

                        <li>
                            <a href="javascript:"><i class="ri-time-line"></i> Periode</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Product Management</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesProducts" aria-expanded="false"
                    aria-controls="sidebarPagesProducts" class="side-nav-link">
                    <i class="bi bi-boxes"></i>
                    <span> Product </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesProducts">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('product-category.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('product-category.index') }}"> --}}
                                    <i class="ri-box-1-line me-1"></i> Kategori Produk
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('product-group.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('product-group.index') }}"> --}}
                                    <i class="ri-box-2-line me-1"></i> Kelompok Produk
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('satuan.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('satuan.index') }}"> --}}
                                    <i class="ri-box-3-line me-1"></i> Satuan
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('product.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('product.index') }}"> --}}
                                    <i class="bi bi-box-seam me-1"></i> Produk
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Inventory & Saldo</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesSaldoManagements" aria-expanded="false"
                    aria-controls="sidebarPagesSaldoManagements" class="side-nav-link">
                    <i class="bi bi-credit-card-2-front"></i>
                    <span> Saldo </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesSaldoManagements">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('saldo-product.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('saldo-product.index') }}"> --}}
                                    <i class="bi bi-people me-1"></i> Produk Saldo
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('saldo-service.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('saldo-service.index') }}"> --}}
                                    <i class="bi bi-truck me-1"></i> Service Saldo
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Business Partners</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesNameCards" aria-expanded="false"
                    aria-controls="sidebarPagesNameCards" class="side-nav-link">
                    <i class="bi bi-person-vcard"></i>
                    <span> Kartu Nama </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesNameCards">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('mitra.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('mitra.index') }}"> --}}
                                    <i class="bi bi-people me-1"></i> Mitra
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('distributor.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('distributor.index') }}"> --}}
                                    <i class="bi bi-truck me-1"></i> Distributor
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('customer.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('customer.index') }}"> --}}
                                    <i class="bi bi-person-check me-1"></i> Pelanggan
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Transaction</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesPurchase" aria-expanded="false"
                    aria-controls="sidebarPagesPurchase" class="side-nav-link">
                    <i class="bi bi-cart-plus"></i>
                    <span> Purchase </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesPurchase">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('purchase-direct.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('purchase-direct.index') }}"> --}}
                                    <i class="bi bi-cart-plus-fill me-1"></i> Pembelian Langsung
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('purchase-order.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('purchase-order.index') }}"> --}}
                                    <i class="bi bi-clipboard-plus me-1"></i> Purchase Order
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('purchase-receive.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('purchase-receive.index') }}"> --}}
                                    <i class="bi bi-box-arrow-in-down me-1"></i> Penerimaan Barang
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('purchase-invoice.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('purchase-invoice.index') }}"> --}}
                                    <i class="bi bi-receipt me-1"></i> Purchase Invoice
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('purchase-payment.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('purchase-payment.index') }}"> --}}
                                    <i class="bi bi-credit-card me-1"></i> Purchase Payment
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesSales" aria-expanded="false"
                    aria-controls="sidebarPagesSales" class="side-nav-link">
                    <i class="bi bi-cart-check"></i>
                    <span> Sales </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesSales">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('pos.index') ? 'active' : '' }}"> --}}
                            <a href="{{ 'javascript:' }}">
                                {{-- <a href="{{ route('pos.index') ?? 'javascript:' }}"> --}}
                                    <i class="bi bi-cash-stack me-1"></i> POS (Point of Sales)
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('direct-sales.index') ? 'active' : '' }}"> --}}
                            <a href="{{ 'javascript:' }}">
                                {{-- <a href="{{ route('direct-sales.index') ?? 'javascript:' }}"> --}}
                                    <i class="bi bi-cart-dash me-1"></i> Penjualan Langsung
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('sales-order.index') ? 'active' : '' }}"> --}}
                            <a href="{{ 'javascript:' }}">
                                {{-- <a href="{{ route('sales-order.index') ?? 'javascript:' }}"> --}}
                                    <i class="bi bi-clipboard-check me-1"></i> Sales Order
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('sales-delivery.index') ? 'active' : '' }}"> --}}
                            <a href="{{ 'javascript:' }}">
                                {{-- <a href="{{ route('sales-delivery.index') ?? 'javascript:' }}"> --}}
                                    <i class="bi bi-truck me-1"></i> Pengiriman Barang
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('sales-invoice.index') ? 'active' : '' }}"> --}}
                            <a href="{{ 'javascript:' }}">
                                {{-- <a href="{{ route('sales-invoice.index') ?? 'javascript:' }}"> --}}
                                    <i class="bi bi-file-earmark-text me-1"></i> Sales Invoice
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('sales-payment.index') ? 'active' : '' }}"> --}}
                            <a href="{{ 'javascript:' }}">
                                {{-- <a href="{{ route('sales-payment.index') ?? 'javascript:' }}"> --}}
                                    <i class="bi bi-cash me-1"></i> Sales Payment
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Financial Management</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesLoan" aria-expanded="false"
                    aria-controls="sidebarPagesLoan" class="side-nav-link">
                    <i class="bi bi-cash-coin"></i>
                    <span> Pinjaman </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesLoan">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('pinjaman.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('pinjaman.index') }}"> --}}
                                    <i class="bi bi-wallet2 me-1"></i> Pinjaman
                                </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesAccounting" aria-expanded="false"
                    aria-controls="sidebarPagesAccounting" class="side-nav-link">
                    <i class="bi bi-journals"></i>
                    <span> Akuntansi </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesAccounting">
                    <ul class="side-nav-second-level">
                        <li class="">

                        <li class="{{ request()->routeIs('journal') ? 'active' : '' }}">
                            <a href="{{ route('journal') }}">
                                <i class="ri-file-list-3-line me-1"></i> Jurnal
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('general-ledger') ? 'active' : '' }}">
                            <a href="{{ route('general-ledger') }}">
                                <i class="ri-book-open-line me-1"></i> Buku Besar
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('trial-balance') ? 'active' : '' }}">
                            <a href="{{ route('trial-balance') }}">
                                <i class="ri-scales-3-line me-1"></i> Neraca Saldo
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('income-statement') ? 'active' : '' }}">
                            <a href="{{ route('income-statement') }}">
                                <i class="ri-line-chart-line me-1"></i> Laba Rugi
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('adjustment-journal') ? 'active' : '' }}">
                            <a href="{{ route('adjustment-journal') }}">
                                <i class="ri-file-edit-line me-1"></i> Jurnal Penyesuaian
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('adjusted-trial-balance') ? 'active' : '' }}">
                            <a href="{{ route('adjusted-trial-balance') }}">
                                <i class="ri-file-edit-line me-1"></i> Neraca Penyesuaian
                            </a>
                        </li>
                        <li>
                            <a href="javascript:">Laporan Keuangan</a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Banking & Reconciliation</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesBank" aria-expanded="false"
                    aria-controls="sidebarPagesBank" class="side-nav-link">
                    <i class="bi bi-bank"></i>
                    <span> Bank </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesBank">
                    <ul class="side-nav-second-level">
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('bank-account.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('bank-account.index') }}"> --}}
                                    <i class="bi bi-credit-card me-1"></i> Rekening Bank
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('bank-transaction.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('bank-transaction.index') }}"> --}}
                                    <i class="bi bi-arrow-left-right me-1"></i> Transaksi Bank
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('bank-fee-template.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('bank-fee-template.index') }}"> --}}
                                    <i class="bi bi-receipt me-1"></i> Template Biaya
                                </a>
                        </li>
                        <li class="">
                            {{--
                        <li class="{{ request()->routeIs('bank-reconciliation.index') ? 'active' : '' }}"> --}}
                            <a href="javascript:">
                                {{-- <a href="{{ route('bank-reconciliation.index') }}"> --}}
                                    <i class="bi bi-check2-square me-1"></i> Rekonsiliasi
                                </a>
                        </li>
                    </ul>
                </div>
            </li>




        </ul>
        <!--- End Sidemenu -->

        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left Sidebar End ========== -->