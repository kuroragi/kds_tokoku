@extends('components.layouts.app')

@section('title', 'Saldo Awal')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Saldo Awal</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Saldo Awal</h4>
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
                            <i class="ri-scales-3-line text-primary me-2"></i>
                            Manajemen Saldo Awal
                        </h5>
                        <p class="text-muted mb-0 small">Atur saldo awal tiap akun (COA) per unit usaha dan periode</p>
                    </div>
                </div>
            </div>

            @livewire('saldo.opening-balance-list')
            @livewire('saldo.opening-balance-form')
        </div>
    </div>
</div>
@endsection
