@extends('components.layouts.app')

@section('title', 'Produk Saldo')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Produk Saldo</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Produk Saldo</h4>
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
                            <i class="ri-shopping-bag-line text-primary me-2"></i>
                            Daftar Produk Saldo
                        </h5>
                        <p class="text-muted mb-0 small">Kelola produk saldo: Pulsa, Token Listrik, dll.</p>
                    </div>
                </div>
            </div>

            @livewire('saldo.saldo-product-list')
            @livewire('saldo.saldo-product-form')
        </div>
    </div>
</div>
@endsection
