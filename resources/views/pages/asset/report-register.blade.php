@extends('components.layouts.app')

@section('title', 'Laporan Daftar Aset')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="#">Laporan Aset</a></li>
                            <li class="breadcrumb-item active">Daftar Aset</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Laporan Daftar Aset</h4>
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
                            <i class="ri-file-list-line text-primary me-2"></i>
                            Laporan Daftar Aset (Register)
                        </h5>
                        <p class="text-muted mb-0 small">Daftar lengkap semua aset beserta detail informasi</p>
                    </div>
                </div>
            </div>

            @livewire('asset.report.asset-register-report')
        </div>
    </div>
</div>
@endsection
