@extends('components.layouts.app')
@section('title', 'Pinjaman Karyawan')
@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pinjaman Karyawan</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Pinjaman Karyawan</h4>
                </div>
            </div>
        </div>
        @include('components.spatials.alert')
        <div id="alert-container"></div>
        @livewire('loan.employee-loan-list')
        @livewire('loan.employee-loan-form')
    </div>
</div>
@endsection
