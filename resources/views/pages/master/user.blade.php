@extends('components.layouts.app')

@section('title', 'Manajemen User')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Manajemen User</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Manajemen User</h4>
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
                            <i class="ri-user-settings-line text-primary me-2"></i>
                            Daftar User
                        </h5>
                        <p class="text-muted mb-0 small">Kelola user, role, dan akses ke dalam sistem</p>
                    </div>
                </div>
            </div>

            @livewire('user-management.user-list')
            @livewire('user-management.user-form')
        </div>
    </div>
</div>
@endsection
