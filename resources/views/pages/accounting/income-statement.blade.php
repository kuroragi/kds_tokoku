@extends('components.layouts.app')

@section('title', 'Laporan Laba Rugi')

@section('content')
<div>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="#">Akuntansi</a></li>
                            <li class="breadcrumb-item active">Laba Rugi</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Laporan Laba Rugi</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Income Statement Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-line-chart-line text-primary me-2"></i>
                            Laporan Laba Rugi
                        </h5>
                        <p class="text-muted mb-0 small">
                            Rincian pendapatan dan beban untuk mengetahui laba atau rugi usaha
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()">
                            <i class="ri-printer-line"></i> Cetak
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm">
                            <i class="ri-file-excel-line"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            @livewire('income-statement.income-statement-index')
        </div>
    </div>
</div>
@endsection
