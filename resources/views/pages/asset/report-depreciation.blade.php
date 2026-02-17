@extends('components.layouts.app')

@section('title', 'Laporan Penyusutan')

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
                            <li class="breadcrumb-item active">Penyusutan per Periode</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Laporan Penyusutan per Periode</h4>
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
                            <i class="ri-line-chart-line text-primary me-2"></i>
                            Laporan Penyusutan per Periode
                        </h5>
                        <p class="text-muted mb-0 small">Detail penyusutan aset berdasarkan periode akuntansi</p>
                    </div>
                </div>
            </div>

            @livewire('asset.report.depreciation-report')
        </div>
    </div>
</div>
@endsection
