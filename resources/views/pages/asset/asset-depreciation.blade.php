@extends('components.layouts.app')

@section('title', 'Penyusutan Aset')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="#">Manajemen Aset</a></li>
                            <li class="breadcrumb-item active">Penyusutan</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Penyusutan Aset</h4>
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
                            Penyusutan Aset
                        </h5>
                        <p class="text-muted mb-0 small">Proses dan lihat catatan penyusutan aset per periode</p>
                    </div>
                </div>
            </div>

            @livewire('asset.asset-depreciation-list')
            @livewire('asset.asset-depreciation-process')
        </div>
    </div>
</div>
@endsection
