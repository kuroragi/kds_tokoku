@extends('components.layouts.app')

@section('title', 'Setting Payroll')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Setting Payroll</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Setting Payroll</h4>
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
                            <i class="ri-settings-3-line text-primary me-2"></i>
                            Pengaturan BPJS & PPh 21
                        </h5>
                        <p class="text-muted mb-0 small">Konfigurasi tarif BPJS dan pengaturan pajak per unit usaha</p>
                    </div>
                </div>
            </div>

            @livewire('payroll.payroll-setting-form')
        </div>
    </div>
</div>
@endsection
