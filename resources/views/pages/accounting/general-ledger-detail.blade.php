@extends('components.layouts.app')

@section('title', "Buku Besar - {$coa->code} {$coa->name}")

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
                            <li class="breadcrumb-item"><a href="{{ route('general-ledger') }}">Buku Besar</a></li>
                            <li class="breadcrumb-item active">{{ $coa->code }} - {{ $coa->name }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Detail Buku Besar</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')

        <!-- Dynamic Alert Container -->
        <div id="alert-container"></div>

        <!-- General Ledger Detail Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <a href="{{ route('general-ledger') }}" class="btn btn-sm btn-outline-secondary"
                            title="Kembali ke Ringkasan">
                            <i class="ri-arrow-left-line"></i>
                        </a>
                        <div>
                            <h5 class="mb-0">
                                <i class="ri-file-list-3-line text-primary me-2"></i>
                                Detail Buku Besar
                            </h5>
                            <p class="text-muted mb-0 small">
                                Daftar seluruh transaksi untuk akun {{ $coa->code }} - {{ $coa->name }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            @livewire('general-ledger.general-ledger-detail', ['coa' => $coa])
        </div>
    </div>
</div>
@endsection
