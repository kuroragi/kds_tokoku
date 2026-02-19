@extends('components.layouts.app')

@section('title', 'Rekening & Kas')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Rekening & Kas</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Rekening & Kas</h4>
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
                            <i class="bi bi-credit-card text-primary me-2"></i>
                            Rekening Bank & Kas
                        </h5>
                        <p class="text-muted mb-0 small">Kelola rekening bank dan kas per unit usaha</p>
                    </div>
                </div>
            </div>

            @livewire('bank.bank-account-list')
            @livewire('bank.bank-account-form')
        </div>
    </div>
</div>
@endsection
