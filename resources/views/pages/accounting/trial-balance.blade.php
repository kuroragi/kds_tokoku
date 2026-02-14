@extends('components.layouts.app')

@section('title', 'Neraca Saldo')

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
                            <li class="breadcrumb-item active">Neraca Saldo</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Neraca Saldo</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Neraca Saldo Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-scales-3-line text-primary me-2"></i>
                            Neraca Saldo
                        </h5>
                        <p class="text-muted mb-0 small">
                            Laporan posisi keuangan: Neraca (balance sheet) dan Neraca Saldo (trial balance)
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

            @livewire('trial-balance.trial-balance-index')
        </div>
    </div>
</div>
@endsection
