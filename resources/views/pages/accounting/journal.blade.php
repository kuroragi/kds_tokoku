@extends('components.layouts.app')

@section('title', 'Manajemen Journal Entry')

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
                            <li class="breadcrumb-item active">Journal Entry</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Manajemen Journal Entry</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')

        <!-- Dynamic Alert Container -->
        <div id="alert-container"></div>

        <!-- Journal Management Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-book-line text-primary me-2"></i>
                            Daftar Journal Entry
                        </h5>
                        <p class="text-muted mb-0 small">
                            Kelola catatan journal entry dan transaksi akuntansi Anda
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
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#journalGuideModal">
                            <i class="ri-question-line"></i> Panduan
                        </button>
                    </div>
                </div>
            </div>

            @livewire('journal.journal-list')
        </div>
    </div>

    <!-- Journal Form Component -->
    @livewire('journal.journal-form')

    <!-- Journal Detail Modal -->
    @livewire('journal.journal-detail')

    @include('pages.guide-modal.journal-guide-modal')
</div>
@endsection

@push('scripts')
<!-- Journal Scripts -->
<script>
    // Konfirmasi Hapus
    function confirmDeleteJournal(journalNo, journalId) {
        Swal.fire({
            title: 'Hapus Journal Entry?',
            html: `Apakah Anda yakin ingin menghapus journal <strong>"${journalNo}"</strong>?<br>Tindakan ini tidak dapat dibatalkan.`,
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
                Livewire.dispatch('deleteJournal', {journalId: journalId});
            }
        });
    }

    // Konfirmasi Posting Journal
    function confirmPostJournal(journalNo, journalId) {
        Swal.fire({
            title: 'Posting Journal Entry?',
            html: `Apakah Anda yakin ingin mem-posting journal <strong>"${journalNo}"</strong>?<br>Journal yang sudah diposting tidak dapat diedit lagi.`,
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
                Livewire.dispatch('postJournal', {journalId: journalId});
            }
        });
    }
</script>
@endpush