@extends('components.layouts.app')

@section('content')
<div class="container-fluid">

    <!-- start page title -->
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Dashboard</h4>
            </div>
        </div>
    </div>
    <!-- end page title -->

    @livewire('dashboard')
</div>
@endsection

@push('scripts')
    <!-- Apex Charts js -->
<script src="/assets/vendor/apexcharts/apexcharts.min.js"></script>
@endpush