@extends('components.layouts.app')

@section('title', 'Jurnal Penyesuaian')

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
                            <li class="breadcrumb-item active">Jurnal Penyesuaian</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Jurnal Penyesuaian</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')

        <!-- Dynamic Alert Container -->
        <div id="alert-container"></div>

        <!-- Adjustment Journal Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-file-edit-line text-info me-2"></i>
                            Daftar Jurnal Penyesuaian
                        </h5>
                        <p class="text-muted mb-0 small">
                            Kelola jurnal penyesuaian akhir periode (penyusutan, akrual, deferal, dll)
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()"
                            title="Cetak Daftar">
                            <i class="ri-printer-line"></i> Cetak
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" title="Export ke Excel">
                            <i class="ri-file-excel-line"></i> Export
                        </button>
                    </div>
                </div>
            </div>

            @livewire('adjustment-journal.adjustment-journal-list')
        </div>
    </div>

    <!-- Adjustment Journal Form Component -->
    @livewire('adjustment-journal.adjustment-journal-form')

    <!-- Journal Detail Modal (reuse existing) -->
    @livewire('journal.journal-detail')
</div>
@endsection

@push('scripts')
<script>
    // Konfirmasi Hapus Jurnal Penyesuaian
    function confirmDeleteAdjustment(journalNo, journalId) {
        Swal.fire({
            title: 'Hapus Jurnal Penyesuaian?',
            html: `Apakah Anda yakin ingin menghapus jurnal penyesuaian <strong>"${journalNo}"</strong>?<br>Tindakan ini tidak dapat dibatalkan.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ri-delete-bin-line"></i> Ya, Hapus',
            cancelButtonText: '<i class="ri-close-line"></i> Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('deleteAdjustment', {journalId: journalId});
            }
        });
    }

    // Konfirmasi Posting Jurnal Penyesuaian
    function confirmPostAdjustment(journalNo, journalId) {
        Swal.fire({
            title: 'Posting Jurnal Penyesuaian?',
            html: `Apakah Anda yakin ingin mem-posting jurnal penyesuaian <strong>"${journalNo}"</strong>?<br>Jurnal yang sudah diposting tidak dapat diedit lagi.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ri-check-line"></i> Ya, Posting',
            cancelButtonText: '<i class="ri-close-line"></i> Batal',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                Livewire.dispatch('postAdjustment', {journalId: journalId});
            }
        });
    }
</script>
@endpush
