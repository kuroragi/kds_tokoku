@extends('components.layouts.app')

@section('title', 'Top Up Saldo')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Top Up Saldo</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Top Up Saldo</h4>
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
                            <i class="ri-wallet-3-line text-success me-2"></i>
                            Riwayat Top Up Saldo
                        </h5>
                        <p class="text-muted mb-0 small">Catat dan kelola top up saldo dari berbagai penyedia</p>
                    </div>
                </div>
            </div>

            @livewire('saldo.saldo-topup-list')
            @livewire('saldo.saldo-topup-form')
        </div>
    </div>
</div>
@endsection
