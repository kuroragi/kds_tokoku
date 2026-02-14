@extends('components.layouts.app')

@section('title', 'Closing Periode')

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
                            <li class="breadcrumb-item active">Closing Periode</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Closing Periode</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        <!-- Closing Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-book-line text-danger me-2"></i>
                            Closing Periode
                        </h5>
                        <p class="text-muted mb-0 small">
                            Tutup periode bulanan dan tahunan
                        </p>
                    </div>
                </div>
            </div>

            @livewire('closing.closing-index')
        </div>
    </div>
</div>
@endsection
