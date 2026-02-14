@extends('components.layouts.app')

@section('title', 'Perpajakan & Closing')

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
                            <li class="breadcrumb-item active">Perpajakan & Closing</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Perpajakan & Closing</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Wizard Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-government-line text-danger me-2"></i>
                            Perpajakan & Closing
                        </h5>
                        <p class="text-muted mb-0 small">
                            Koreksi Fiskal → Perhitungan Pajak → Jurnal Pajak → Closing Bulanan → Closing Tahunan
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()">
                            <i class="ri-printer-line"></i> Cetak
                        </button>
                    </div>
                </div>
            </div>

            @livewire('tax-closing.tax-closing-wizard')
        </div>
    </div>
</div>
@endsection
