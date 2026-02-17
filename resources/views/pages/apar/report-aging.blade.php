@extends('components.layouts.app')

@section('title', 'Laporan Aging AP/AR')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="#">AP/AR</a></li>
                            <li class="breadcrumb-item active">Aging</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Laporan Aging Hutang / Piutang</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div>
                    <h5 class="mb-0">
                        <i class="ri-time-line text-primary me-2"></i>
                        Aging Report
                    </h5>
                    <p class="text-muted mb-0 small">Analisa umur hutang / piutang berdasarkan jatuh tempo</p>
                </div>
            </div>
            <div class="card-body">
                @livewire('ap-ar.report.aging-report')
            </div>
        </div>
    </div>
</div>
@endsection
