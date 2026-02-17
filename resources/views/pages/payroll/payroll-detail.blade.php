@extends('components.layouts.app')

@section('title', $payrollPeriod->name)

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('payroll.index') }}">Payroll</a></li>
                            <li class="breadcrumb-item active">{{ $payrollPeriod->name }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Detail Payroll</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        @livewire('payroll.payroll-detail', ['payrollPeriod' => $payrollPeriod])
        @livewire('payroll.payroll-slip')
    </div>
</div>
@endsection
