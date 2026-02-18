@extends('components.layouts.app')

@section('title', 'Transaksi Saldo')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Transaksi Saldo</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Transaksi Saldo</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-exchange-funds-line text-warning me-2"></i>
                            Transaksi Penjualan Saldo
                        </h5>
                        <p class="text-muted mb-0 small">Catat penjualan pulsa, token listrik, dan produk saldo lainnya</p>
                    </div>
                </div>
            </div>

            @livewire('saldo.saldo-transaction-list')
            @livewire('saldo.saldo-transaction-form')
        </div>
    </div>
</div>
@endsection
