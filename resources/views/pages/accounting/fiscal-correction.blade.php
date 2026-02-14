@extends('components.layouts.app')

@section('title', 'Koreksi Fiskal')

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
                            <li class="breadcrumb-item active">Koreksi Fiskal</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Koreksi Fiskal</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Koreksi Fiskal Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-exchange-line text-warning me-2"></i>
                            Koreksi Fiskal
                        </h5>
                        <p class="text-muted mb-0 small">
                            Kelola koreksi fiskal positif dan negatif (Beda Tetap & Beda Waktu)
                        </p>
                    </div>
                </div>
            </div>

            @livewire('fiscal-correction.fiscal-correction-index')
        </div>
    </div>
</div>
@endsection
