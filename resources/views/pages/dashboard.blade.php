@extends('components.layouts.app')

@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <div class="page-title-right">
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#dateRangeModal">
                        <i class="ri-calendar-2-line"></i> Filter Tanggal
                    </button>
                </div>
                <h4 class="page-title">Dashboard</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    <div class="row">
        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-pink">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ri-shopping-cart-line widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0">Total Penjualan</h6>
                    <h2 class="my-2">Rp 0,-</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-10 me-1">-</span>
                        <span class="text-nowrap">Bulan ini</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-purple">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ri-file-invoice-line widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0">Total Invoice</h6>
                    <h2 class="my-2">0</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-10 me-1">-</span>
                        <span class="text-nowrap">Bulan ini</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-info">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ri-shopping-bag-line widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0">Produk Terjual</h6>
                    <h2 class="my-2">0</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-25 me-1">-</span>
                        <span class="text-nowrap">Bulan ini</span>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-xxl-3 col-sm-6">
            <div class="card widget-flat text-bg-primary">
                <div class="card-body">
                    <div class="float-end">
                        <i class="ri-user-add-line widget-icon"></i>
                    </div>
                    <h6 class="text-uppercase mt-0">Customer Baru</h6>
                    <h2 class="my-2">0</h2>
                    <p class="mb-0">
                        <span class="badge bg-white bg-opacity-10 me-1">-</span>
                        <span class="text-nowrap">Bulan ini</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        <a href="javascript:;" data-bs-toggle="reload"><i class="ri-refresh-line"></i></a>
                        <a data-bs-toggle="collapse" href="#sales-chart-collapse" role="button" aria-expanded="false"
                            aria-controls="sales-chart-collapse"><i class="ri-subtract-line"></i></a>
                        <a href="#" data-bs-toggle="remove"><i class="ri-close-line"></i></a>
                    </div>
                    <h5 class="header-title mb-3">Grafik Penjualan</h5>

                    <div id="sales-chart-collapse" class="collapse pt-3 show">
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <strong>Info:</strong> Grafik penjualan akan ditampilkan setelah integrasi data.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <div class="text-center py-5">
                            <i class="ri-bar-chart-line" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">Chart Placeholder</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        <a href="javascript:;" data-bs-toggle="reload"><i class="ri-refresh-line"></i></a>
                        <a data-bs-toggle="collapse" href="#top-products-collapse" role="button" aria-expanded="false"
                            aria-controls="top-products-collapse"><i class="ri-subtract-line"></i></a>
                        <a href="#" data-bs-toggle="remove"><i class="ri-close-line"></i></a>
                    </div>
                    <h5 class="header-title mb-3">Produk Terlaris</h5>

                    <div id="top-products-collapse" class="collapse show">
                        <div class="alert alert-warning alert-dismissible fade show" role="alert">
                            <strong>Placeholder:</strong> Data produk terlaris akan tampil di sini.
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <div class="text-center py-5">
                            <i class="ri-shopping-bag-line" style="font-size: 48px; color: #ccc;"></i>
                            <p class="text-muted mt-3">No Data</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        <a href="javascript:;" data-bs-toggle="reload"><i class="ri-refresh-line"></i></a>
                        <a data-bs-toggle="collapse" href="#summary-collapse" role="button" aria-expanded="false"
                            aria-controls="summary-collapse"><i class="ri-subtract-line"></i></a>
                        <a href="#" data-bs-toggle="remove"><i class="ri-close-line"></i></a>
                    </div>
                    <h5 class="header-title mb-3">Ringkasan Hari Ini</h5>

                    <div id="summary-collapse" class="collapse show">
                        <div class="row">
                            <div class="col-6 text-center mb-3">
                                <h6 class="text-muted text-uppercase mb-2">Transaksi</h6>
                                <h3 class="mb-0">0</h3>
                            </div>
                            <div class="col-6 text-center mb-3">
                                <h6 class="text-muted text-uppercase mb-2">Penjualan</h6>
                                <h3 class="mb-0">Rp 0</h3>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row">
                            <div class="col-6 text-center mb-3">
                                <h6 class="text-muted text-uppercase mb-2">Pelanggan</h6>
                                <h3 class="mb-0">0</h3>
                            </div>
                            <div class="col-6 text-center mb-3">
                                <h6 class="text-muted text-uppercase mb-2">Item Terjual</h6>
                                <h3 class="mb-0">0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        <a href="javascript:;" data-bs-toggle="reload"><i class="ri-refresh-line"></i></a>
                        <a data-bs-toggle="collapse" href="#cash-flow-collapse" role="button" aria-expanded="false"
                            aria-controls="cash-flow-collapse"><i class="ri-subtract-line"></i></a>
                        <a href="#" data-bs-toggle="remove"><i class="ri-close-line"></i></a>
                    </div>
                    <h5 class="header-title mb-3">Cash Flow</h5>

                    <div id="cash-flow-collapse" class="collapse show">
                        <div class="list-group">
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                <span class="text-success"><i class="ri-arrow-up-line"></i> Pemasukan</span>
                                <strong>Rp 0</strong>
                            </div>
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-0">
                                <span class="text-danger"><i class="ri-arrow-down-line"></i> Pengeluaran</span>
                                <strong>Rp 0</strong>
                            </div>
                            <div
                                class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-top">
                                <span class="text-primary"><i class="ri-wallet-line"></i> Saldo</span>
                                <strong>Rp 0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body">
                    <div class="card-widgets">
                        <a href="javascript:;" data-bs-toggle="reload"><i class="ri-refresh-line"></i></a>
                        <a data-bs-toggle="collapse" href="#status-collapse" role="button" aria-expanded="false"
                            aria-controls="status-collapse"><i class="ri-subtract-line"></i></a>
                        <a href="#" data-bs-toggle="remove"><i class="ri-close-line"></i></a>
                    </div>
                    <h5 class="header-title mb-3">Status Sistem</h5>

                    <div id="status-collapse" class="collapse show">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2">
                                <span class="badge bg-success-subtle text-success">Online</span>
                                <small class="text-muted">Server</small>
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-success-subtle text-success">Connected</span>
                                <small class="text-muted">Database</small>
                            </li>
                            <li class="mb-2">
                                <span class="badge bg-warning-subtle text-warning">Pending</span>
                                <small class="text-muted">Backup</small>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection