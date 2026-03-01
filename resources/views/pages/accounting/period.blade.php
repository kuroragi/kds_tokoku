@extends('components.layouts.app')

@section('title', 'Manajemen Periode')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="#">Akuntansi</a></li>
                            <li class="breadcrumb-item active">Periode</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Manajemen Periode</h4>
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
                            <i class="ri-calendar-line text-primary me-2"></i>
                            Daftar Periode Akuntansi
                        </h5>
                        <p class="text-muted mb-0 small">Kelola periode bulanan untuk pencatatan jurnal</p>
                    </div>
                    @if(auth()->user()->hasRole('superadmin'))
                    <button class="btn btn-primary btn-sm" onclick="Livewire.dispatch('openPeriodModal')">
                        <i class="ri-add-line me-1"></i> Tambah Periode
                    </button>
                    @endif
                </div>
            </div>

            @livewire('closing.period-list')
            @livewire('closing.period-form')
        </div>
    </div>
</div>
@endsection
