@extends('components.layouts.app')

@section('title', isset($unit) ? 'Edit Unit Usaha' : 'Tambah Unit Usaha')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="{{ route('business-unit.index') }}">Unit Usaha</a></li>
                            <li class="breadcrumb-item active">{{ isset($unit) ? 'Edit' : 'Tambah' }}</li>
                        </ol>
                    </div>
                    <h4 class="page-title">{{ isset($unit) ? 'Edit Unit Usaha' : 'Tambah Unit Usaha Baru' }}</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')
        <div id="alert-container"></div>

        @livewire('business-unit.business-unit-form', ['unitId' => isset($unit) ? $unit->id : null])
    </div>
</div>
@endsection
