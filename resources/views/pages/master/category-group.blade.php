@extends('components.layouts.app')

@section('title', 'Grup Kategori')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Grup Kategori</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Grup Kategori</h4>
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
                            <i class="ri-folder-line text-primary me-2"></i>
                            Daftar Grup Kategori
                        </h5>
                        <p class="text-muted mb-0 small">Kelola grup kategori dengan akun persediaan, pendapatan, dan biaya</p>
                    </div>
                </div>
            </div>

            @livewire('stock-management.category-group-list')
            @livewire('stock-management.category-group-form')
        </div>
    </div>
</div>
@endsection
