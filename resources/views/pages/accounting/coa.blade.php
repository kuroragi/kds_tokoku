@extends('components.layouts.app')

@section('title', 'Chart of Accounts')

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
                            <li class="breadcrumb-item"><a href="#">Config Accounting</a></li>
                            <li class="breadcrumb-item active">Chart of Accounts</li>
                        </ol>
                    </div>
                    <h4 class="page-title">Chart of Accounts Management</h4>
                </div>
            </div>
        </div>

        @include('components.spatials.alert')

        <!-- Dynamic Alert Container -->
        <div id="alert-container"></div>

        <!-- COA Management Content -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-file-list-line text-primary me-2"></i>
                            Chart of Accounts
                        </h5>
                        <p class="text-muted mb-0 small">
                            Manage your financial account structure and hierarchy
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-info btn-sm" onclick="window.print()"
                            title="Print List">
                            <i class="ri-printer-line"></i> Print
                        </button>
                        <button type="button" class="btn btn-outline-success btn-sm" title="Export to Excel">
                            <i class="ri-file-excel-line"></i> Export
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal"
                            data-bs-target="#coaGuideModal">
                            <i class="ri-question-line"></i> Guide
                        </button>
                    </div>
                </div>
            </div>

            @livewire('coa.coa-list' )
        </div>
    </div>

    <!-- COA Form Component -->
    @livewire('coa.coa-form')

    @include('pages.guide-modal.coa-guide-modal')
</div>
@endsection


@push('scripts')
<!-- Delete Confirmation Script -->
{{-- <script>
    function confirmDeleteCoa(coaName, coaId) {
        Swal.fire({
            title: 'Delete Chart of Account?',
            html: `Are you sure you want to delete <strong>"${coaName}"</strong>?<br>This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ri-delete-bin-line"></i> Yes, Delete',
            cancelButtonText: '<i class="ri-close-line"></i> Cancel',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return true;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('deleteCoa', coaId);
            }
        });
    }
</script> --}}
@endpush