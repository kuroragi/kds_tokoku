@extends('components.layouts.app')

@section('title', 'Buku Besar (General Ledger)')

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
                            <li class="breadcrumb-item active">Buku Besar</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Buku Besar (General Ledger)</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')

        <!-- Dynamic Alert Container -->
        <div id="alert-container"></div>

        <!-- General Ledger Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-book-open-line text-primary me-2"></i>
                            Buku Besar
                        </h5>
                        <p class="text-muted mb-0 small">
                            Rincian transaksi per akun dari jurnal yang sudah diposting
                        </p>
                    </div>
                </div>
            </div>

            @livewire('general-ledger.general-ledger-index')
        </div>
    </div>
</div>
@endsection
