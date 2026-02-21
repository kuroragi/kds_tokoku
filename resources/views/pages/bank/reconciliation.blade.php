@extends('components.layouts.app')

@section('title', 'Rekonsiliasi Bank')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Rekonsiliasi Bank</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Rekonsiliasi Bank</h4>
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
                            <i class="bi bi-check2-square text-primary me-2"></i>
                            Rekonsiliasi Bank
                        </h5>
                        <p class="text-muted mb-0 small">Cocokkan mutasi bank dengan transaksi di sistem</p>
                    </div>
                </div>
            </div>

            @livewire('bank.bank-reconciliation-list')
        </div>
    </div>
</div>
@endsection
