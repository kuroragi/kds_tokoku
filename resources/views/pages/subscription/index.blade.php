@extends('components.layouts.app')

@section('title', 'Manajemen Langganan')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Manajemen Langganan</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Manajemen Langganan</h4>
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
                            <i class="ri-vip-crown-line text-primary me-2"></i>
                            Langganan Pengguna
                        </h5>
                        <p class="text-muted mb-0 small">Kelola dan aktivasi pembayaran langganan TOKOKU</p>
                    </div>
                </div>
            </div>

            <div class="card-body">
                @livewire('subscription-management')
            </div>
        </div>
    </div>
</div>
@endsection
