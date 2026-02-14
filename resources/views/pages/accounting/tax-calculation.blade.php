@extends('components.layouts.app')

@section('title', 'Perhitungan Pajak')

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
                            <li class="breadcrumb-item active">Perhitungan Pajak</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Perhitungan Pajak</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Tax Calculation Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-government-line text-danger me-2"></i>
                            Perhitungan Pajak Penghasilan Badan
                        </h5>
                        <p class="text-muted mb-0 small">
                            Laba Komersial → Koreksi Fiskal → Laba Fiskal → Kompensasi Rugi → PKP → PPh Terutang
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()">
                            <i class="ri-printer-line"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>

            @livewire('tax-calculation.tax-calculation-index')
        </div>
    </div>
</div>
@endsection
