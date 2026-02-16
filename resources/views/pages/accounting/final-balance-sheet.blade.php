@extends('components.layouts.app')

@section('title', 'Neraca Keuangan Final')

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
                            <li class="breadcrumb-item"><a href="#">Laporan Keuangan</a></li>
                            <li class="breadcrumb-item active">Neraca Keuangan Final</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Neraca Keuangan Final</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-scales-3-line text-primary me-2"></i>
                            Neraca Keuangan Final
                        </h5>
                        <p class="text-muted mb-0 small">
                            Laporan lengkap posisi keuangan: Neraca, Laba Rugi, dan Ringkasan Keuangan
                        </p>
                    </div>
                </div>
            </div>

            @livewire('report.final-balance-sheet')
        </div>
    </div>
</div>
@endsection
