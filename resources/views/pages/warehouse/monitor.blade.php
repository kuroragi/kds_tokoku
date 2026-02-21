@extends('components.layouts.app')

@section('title', 'Monitor Gudang')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Monitor Gudang</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Monitor Gudang</h4>
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
                            <i class="ri-building-4-line text-warning me-2"></i>
                            Monitor Stok & Saldo
                        </h5>
                        <p class="text-muted mb-0 small">Pantau barang dan saldo yang mendekati atau di bawah batas minimum</p>
                    </div>
                </div>
            </div>

            @livewire('stock-management.warehouse-monitor')
        </div>
    </div>
</div>
@endsection
