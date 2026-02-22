@extends('components.layouts.app')

@section('title', 'Pengaturan Sistem')

@section('content')
<div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <div class="page-title-right">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                            <li class="breadcrumb-item active">Pengaturan Sistem</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Pengaturan Sistem</h4>
                </div>
            </div>
        </div>

        @livewire('system-settings')
    </div>
</div>
@endsection
