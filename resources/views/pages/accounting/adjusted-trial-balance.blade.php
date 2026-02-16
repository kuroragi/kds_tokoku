@extends('components.layouts.app')

@section('title', 'Neraca Penyesuaian')

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
                            <li class="breadcrumb-item active">Neraca Penyesuaian</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Neraca Penyesuaian</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Neraca Penyesuaian Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-file-edit-line text-info me-2"></i>
                            Neraca Penyesuaian
                        </h5>
                        <p class="text-muted mb-0 small">
                            Worksheet: Neraca Saldo, Penyesuaian, dan Neraca Saldo Disesuaikan
                        </p>
                    </div>
                </div>
            </div>

            @livewire('adjusted-trial-balance.adjusted-trial-balance-index')
        </div>
    </div>
</div>
@endsection
