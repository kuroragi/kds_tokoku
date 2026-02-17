@extends('components.layouts.app')

@section('title', 'Mutasi Aset')

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
                            <li class="breadcrumb-item active">Mutasi</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Mutasi Aset</h4>
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
                            <i class="ri-arrow-left-right-line text-primary me-2"></i>
                            Mutasi Lokasi Aset
                        </h5>
                        <p class="text-muted mb-0 small">Catat perpindahan lokasi atau unit aset</p>
                    </div>
                </div>
            </div>

            @livewire('asset.asset-transfer-list')
            @livewire('asset.asset-transfer-form')
        </div>
    </div>
</div>
@endsection
