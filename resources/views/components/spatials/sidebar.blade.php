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
                        <li class="{{ request()->routeIs('business-unit.*') ? 'active' : '' }}">
                            <a href="{{ route('business-unit.index') }}">
                                <i class="ri-store-2-line me-1"></i> Unit Usaha
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('user.*') ? 'active' : '' }}">
                            <a href="{{ route('user.index') }}">
                                <i class="ri-user-line me-1"></i> User
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('role.*') ? 'active' : '' }}">
                            <a href="{{ route('role.index') }}">
                                <i class="ri-shield-user-line me-1"></i> Role
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('permission.*') ? 'active' : '' }}">
                            <a href="{{ route('permission.index') }}">
                                <i class="ri-key-2-line me-1"></i> Permission
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('position.*') ? 'active' : '' }}">
                            <a href="{{ route('position.index') }}">
                                <i class="ri-briefcase-line me-1"></i> Jabatan
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

                        <li class="{{ request()->routeIs('opening-balance.*') ? 'active' : '' }}">
                            <a href="{{ route('opening-balance.index') }}">
                                <i class="ri-scales-3-line me-1"></i> Saldo Awal
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Product Management</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesProducts" aria-expanded="false"
                    aria-controls="sidebarPagesProducts" class="side-nav-link">
                    <i class="bi bi-boxes"></i>
                    <span> Stok </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesProducts">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('stock-category.index') ? 'active' : '' }}">
                            <a href="{{ route('stock-category.index') }}">
                                <i class="ri-archive-line me-1"></i> Kategori Stok
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('category-group.index') ? 'active' : '' }}">
                            <a href="{{ route('category-group.index') }}">
                                <i class="ri-folder-line me-1"></i> Grup Kategori
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('unit-of-measure.index') ? 'active' : '' }}">
                            <a href="{{ route('unit-of-measure.index') }}">
                                <i class="ri-ruler-line me-1"></i> Satuan
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('stock.index') ? 'active' : '' }}">
                            <a href="{{ route('stock.index') }}">
                                <i class="ri-shopping-bag-line me-1"></i> Stok
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
                        <li class="{{ request()->routeIs('saldo-provider.index') ? 'active' : '' }}">
                            <a href="{{ route('saldo-provider.index') }}">
                                <i class="bi bi-credit-card-2-front me-1"></i> Penyedia Saldo
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('saldo-product.index') ? 'active' : '' }}">
                            <a href="{{ route('saldo-product.index') }}">
                                <i class="ri-shopping-bag-line me-1"></i> Produk Saldo
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('saldo-topup.index') ? 'active' : '' }}">
                            <a href="{{ route('saldo-topup.index') }}">
                                <i class="ri-wallet-3-line me-1"></i> Top Up Saldo
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('saldo-transaction.index') ? 'active' : '' }}">
                            <a href="{{ route('saldo-transaction.index') }}">
                                <i class="ri-exchange-funds-line me-1"></i> Transaksi Saldo
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
                        <li class="{{ request()->routeIs('employee.*') ? 'active' : '' }}">
                            <a href="{{ route('employee.index') }}">
                                <i class="ri-user-line me-1"></i> Karyawan
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('customer.*') ? 'active' : '' }}">
                            <a href="{{ route('customer.index') }}">
                                <i class="ri-user-heart-line me-1"></i> Pelanggan
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('vendor.*') ? 'active' : '' }}">
                            <a href="{{ route('vendor.index') }}">
                                <i class="ri-truck-line me-1"></i> Vendor
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('partner.*') ? 'active' : '' }}">
                            <a href="{{ route('partner.index') }}">
                                <i class="ri-team-line me-1"></i> Partner
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Asset Management</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesAsset" aria-expanded="false"
                    aria-controls="sidebarPagesAsset" class="side-nav-link">
                    <i class="ri-tools-line"></i>
                    <span> Manajemen Aset </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesAsset">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('asset-category.*') ? 'active' : '' }}">
                            <a href="{{ route('asset-category.index') }}">
                                <i class="ri-folder-settings-line me-1"></i> Kategori Aset
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset.index') ? 'active' : '' }}">
                            <a href="{{ route('asset.index') }}">
                                <i class="ri-archive-drawer-line me-1"></i> Daftar Aset
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-depreciation.*') ? 'active' : '' }}">
                            <a href="{{ route('asset-depreciation.index') }}">
                                <i class="ri-line-chart-line me-1"></i> Penyusutan
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-transfer.*') ? 'active' : '' }}">
                            <a href="{{ route('asset-transfer.index') }}">
                                <i class="ri-arrow-left-right-line me-1"></i> Mutasi
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-repair.*') ? 'active' : '' }}">
                            <a href="{{ route('asset-repair.index') }}">
                                <i class="ri-hammer-line me-1"></i> Perbaikan
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-disposal.*') ? 'active' : '' }}">
                            <a href="{{ route('asset-disposal.index') }}">
                                <i class="ri-delete-bin-line me-1"></i> Disposal
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesAssetReport" aria-expanded="false"
                    aria-controls="sidebarPagesAssetReport" class="side-nav-link">
                    <i class="ri-file-chart-line"></i>
                    <span> Laporan Aset </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesAssetReport">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('asset-report.register') ? 'active' : '' }}">
                            <a href="{{ route('asset-report.register') }}">
                                <i class="ri-list-check-2 me-1"></i> Daftar Aset
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-report.book-value') ? 'active' : '' }}">
                            <a href="{{ route('asset-report.book-value') }}">
                                <i class="ri-money-dollar-circle-line me-1"></i> Nilai Buku
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-report.depreciation') ? 'active' : '' }}">
                            <a href="{{ route('asset-report.depreciation') }}">
                                <i class="ri-bar-chart-grouped-line me-1"></i> Penyusutan per Periode
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('asset-report.history') ? 'active' : '' }}">
                            <a href="{{ route('asset-report.history') }}">
                                <i class="ri-history-line me-1"></i> Riwayat Aset
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
                    <span> Pembelian </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesPurchase">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('purchase-order.*') ? 'active' : '' }}">
                            <a href="{{ route('purchase-order.index') }}">
                                <i class="bi bi-clipboard-plus me-1"></i> Purchase Order
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('purchase.*') ? 'active' : '' }}">
                            <a href="{{ route('purchase.index') }}">
                                <i class="bi bi-cart-plus-fill me-1"></i> Pembelian
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesOpname" aria-expanded="false"
                    aria-controls="sidebarPagesOpname" class="side-nav-link">
                    <i class="ri-clipboard-line"></i>
                    <span> Opname </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesOpname">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('stock-opname.*') ? 'active' : '' }}">
                            <a href="{{ route('stock-opname.index') }}">
                                <i class="ri-store-line me-1"></i> Stock Opname
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('saldo-opname.*') ? 'active' : '' }}">
                            <a href="{{ route('saldo-opname.index') }}">
                                <i class="ri-wallet-line me-1"></i> Saldo Opname
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesSales" aria-expanded="false"
                    aria-controls="sidebarPagesSales" class="side-nav-link">
                    <i class="bi bi-cart-check"></i>
                    <span> Penjualan </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesSales">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('sales.*') ? 'active' : '' }}">
                            <a href="{{ route('sales.index') }}">
                                <i class="ri-shopping-bag-line me-1"></i> Penjualan
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a href="{{ route('warehouse.monitor') }}" class="side-nav-link {{ request()->routeIs('warehouse.*') ? 'active' : '' }}">
                    <i class="ri-building-4-line"></i>
                    <span> Monitor Gudang </span>
                </a>
            </li>

            <li class="side-nav-title">Financial Management</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesApAr" aria-expanded="false"
                    aria-controls="sidebarPagesApAr" class="side-nav-link">
                    <i class="ri-exchange-funds-line"></i>
                    <span> Hutang / Piutang </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesApAr">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('payable.*') ? 'active' : '' }}">
                            <a href="{{ route('payable.index') }}">
                                <i class="ri-money-dollar-box-line me-1"></i> Hutang Usaha
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('receivable.*') ? 'active' : '' }}">
                            <a href="{{ route('receivable.index') }}">
                                <i class="ri-hand-coin-line me-1"></i> Piutang Usaha
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesApArReport" aria-expanded="false"
                    aria-controls="sidebarPagesApArReport" class="side-nav-link">
                    <i class="ri-file-chart-line"></i>
                    <span> Laporan AP/AR </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesApArReport">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('apar-report.aging') ? 'active' : '' }}">
                            <a href="{{ route('apar-report.aging') }}">
                                <i class="ri-timer-line me-1"></i> Aging Report
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('apar-report.outstanding') ? 'active' : '' }}">
                            <a href="{{ route('apar-report.outstanding') }}">
                                <i class="ri-file-list-3-line me-1"></i> Outstanding Report
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('apar-report.payment-history') ? 'active' : '' }}">
                            <a href="{{ route('apar-report.payment-history') }}">
                                <i class="ri-history-line me-1"></i> Riwayat Pembayaran
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesLoan" aria-expanded="false"
                    aria-controls="sidebarPagesLoan" class="side-nav-link">
                    <i class="bi bi-cash-coin"></i>
                    <span> Pinjaman </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesLoan">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('employee-loan.*') ? 'active' : '' }}">
                            <a href="{{ route('employee-loan.index') }}">
                                <i class="bi bi-wallet2 me-1"></i> Pinjaman Karyawan
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Payroll</li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesPayroll" aria-expanded="false"
                    aria-controls="sidebarPagesPayroll" class="side-nav-link">
                    <i class="ri-money-dollar-circle-line"></i>
                    <span> Penggajian </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesPayroll">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('salary-component.*') ? 'active' : '' }}">
                            <a href="{{ route('salary-component.index') }}">
                                <i class="ri-list-check me-1"></i> Komponen Gaji
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('payroll-setting.*') ? 'active' : '' }}">
                            <a href="{{ route('payroll-setting.index') }}">
                                <i class="ri-settings-3-line me-1"></i> Setting Payroll
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('payroll.*') ? 'active' : '' }}">
                            <a href="{{ route('payroll.index') }}">
                                <i class="ri-wallet-3-line me-1"></i> Payroll
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesPayrollReport" aria-expanded="false"
                    aria-controls="sidebarPagesPayrollReport" class="side-nav-link">
                    <i class="ri-bar-chart-box-line"></i>
                    <span> Laporan Payroll </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesPayrollReport">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('payroll-report.recap') ? 'active' : '' }}">
                            <a href="{{ route('payroll-report.recap') }}">
                                <i class="ri-file-chart-line me-1"></i> Rekap Payroll
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('payroll-report.employee') ? 'active' : '' }}">
                            <a href="{{ route('payroll-report.employee') }}">
                                <i class="ri-user-line me-1"></i> Laporan per Karyawan
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('payroll-report.bpjs') ? 'active' : '' }}">
                            <a href="{{ route('payroll-report.bpjs') }}">
                                <i class="ri-shield-cross-line me-1"></i> Laporan BPJS
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
                        <li class="{{ request()->routeIs('tax-closing') ? 'active' : '' }}">
                            <a href="{{ route('tax-closing') }}">
                                <i class="ri-government-line me-1"></i> Perpajakan & Closing
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('tax-report.*') ? 'active' : '' }}">
                            <a href="{{ route('tax-report.index') }}">
                                <i class="ri-file-list-3-line me-1"></i> Laporan Pajak
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-item">
                <a data-bs-toggle="collapse" href="#sidebarPagesReports" aria-expanded="false"
                    aria-controls="sidebarPagesReports" class="side-nav-link">
                    <i class="ri-file-chart-line"></i>
                    <span> Laporan Keuangan </span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPagesReports">
                    <ul class="side-nav-second-level">
                        <li class="{{ request()->routeIs('report.final-balance-sheet') ? 'active' : '' }}">
                            <a href="{{ route('report.final-balance-sheet') }}">
                                <i class="ri-scales-3-line me-1"></i> Neraca Keuangan Final
                            </a>
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
                        <li class="{{ request()->routeIs('bank.index') ? 'active' : '' }}">
                            <a href="{{ route('bank.index') }}">
                                <i class="bi bi-bank me-1"></i> Daftar Bank
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('bank-account.index') ? 'active' : '' }}">
                            <a href="{{ route('bank-account.index') }}">
                                <i class="bi bi-credit-card me-1"></i> Rekening & Kas
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('fund-transfer.index') ? 'active' : '' }}">
                            <a href="{{ route('fund-transfer.index') }}">
                                <i class="bi bi-arrow-left-right me-1"></i> Transfer Dana
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('bank-mutation.index') ? 'active' : '' }}">
                            <a href="{{ route('bank-mutation.index') }}">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Mutasi Bank
                            </a>
                        </li>
                        <li class="{{ request()->routeIs('bank-reconciliation.index') ? 'active' : '' }}">
                            <a href="{{ route('bank-reconciliation.index') }}">
                                <i class="bi bi-check2-square me-1"></i> Rekonsiliasi
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="side-nav-title">Project Management</li>

            <li class="side-nav-item">
                <a href="{{ route('project.index') }}" class="side-nav-link {{ request()->routeIs('project.*') ? 'active' : '' }}">
                    <i class="bi bi-briefcase"></i>
                    <span> Proyek / Job Order </span>
                </a>
            </li>


        </ul>
        <!--- End Sidemenu -->

        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left Sidebar End ========== -->