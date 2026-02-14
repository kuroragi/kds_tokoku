<div class="card-body p-0">
    <!-- Search and Filter Controls -->
    <div class="bg-light p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Pencarian</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="ri-search-line"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Cari nomor jurnal, referensi..."
                        wire:model.live.debounce.300ms="search">
                </div>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Status</label>
                <select class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua Status</option>
                    @foreach($statuses as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Periode</label>
                <select class="form-select" wire:model.live="filterPeriod">
                    <option value="">Semua Periode</option>
                    @foreach($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Tanggal Dari</label>
                <input type="date" class="form-control" wire:model.live="dateFrom">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Tanggal Sampai</label>
                <input type="date" class="form-control" wire:model.live="dateTo">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters"
                    title="Bersihkan Filter">
                    <i class="ri-filter-off-line"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="p-3 border-bottom">
        <div class="row align-items-center">
            <div class="col-md-3">
                <select class="form-select form-select-sm" wire:model.live="perPage">
                    <option value="25">25 per halaman</option>
                    <option value="50">50 per halaman</option>
                    <option value="100">100 per halaman</option>
                </select>
            </div>
            <div class="col-md-9 text-end">
                <button class="btn btn-primary" wire:click="$dispatch('openAdjustmentModal')">
                    <i class="ri-add-line"></i> Tambah Jurnal Penyesuaian
                </button>
            </div>
        </div>
    </div>

    <!-- Adjustment Journal Table -->
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th scope="col" width="12%">
                        <button type="button" class="btn btn-link text-white p-0 text-start text-decoration-none"
                            wire:click="sortBy('journal_no')">
                            No. Jurnal
                            @if ($sortField === 'journal_no')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                            @endif
                        </button>
                    </th>
                    <th scope="col" width="10%">
                        <button type="button" class="btn btn-link text-white p-0 text-start text-decoration-none"
                            wire:click="sortBy('journal_date')">
                            Tanggal
                            @if ($sortField === 'journal_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                            @endif
                        </button>
                    </th>
                    <th scope="col" width="15%">Referensi</th>
                    <th scope="col" width="25%">Keterangan</th>
                    <th scope="col" width="12%" class="text-end">
                        <button type="button" class="btn btn-link text-white p-0 text-end text-decoration-none"
                            wire:click="sortBy('total_debit')">
                            Jumlah
                            @if ($sortField === 'total_debit')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i>
                            @endif
                        </button>
                    </th>
                    <th scope="col" width="8%" class="text-center">Status</th>
                    <th scope="col" width="18%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($journals as $journal)
                <tr wire:key="adjustment-{{ $journal->id }}">
                    <td>
                        <strong class="text-primary">{{ $journal->journal_no }}</strong>
                    </td>
                    <td>
                        {{ $journal->journal_date->format('d M Y') }}
                    </td>
                    <td>
                        <span class="text-muted">{{ $journal->reference ?? '-' }}</span>
                    </td>
                    <td>
                        <div class="text-truncate" style="max-width: 200px;" title="{{ $journal->description }}">
                            {{ $journal->description ?? '-' }}
                        </div>
                    </td>
                    <td class="text-end">
                        <strong>{{ number_format($journal->total_debit, 0, ',', '.') }}</strong>
                        <div class="small text-muted">
                            @if($journal->is_balanced)
                            <i class="ri-check-line text-success"></i> Seimbang
                            @else
                            <i class="ri-close-line text-danger"></i> Tidak Seimbang
                            @endif
                        </div>
                    </td>
                    <td class="text-center">
                        @if($journal->status === 'draft')
                        <span class="badge bg-warning">
                            <i class="ri-draft-line"></i> Draft
                        </span>
                        @elseif($journal->status === 'posted')
                        <span class="badge bg-success">
                            <i class="ri-check-line"></i> Posted
                        </span>
                        @else
                        <span class="badge bg-danger">
                            <i class="ri-close-line"></i> Dibatalkan
                        </span>
                        @endif
                    </td>
                    <td class="text-center">
                        <div class="btn-group btn-group-sm" role="group">
                            <!-- Lihat Detail -->
                            <button type="button" class="btn btn-outline-info"
                                wire:click="$dispatch('viewJournalDetail', { journalId: {{ $journal->id }} })"
                                title="Lihat Detail">
                                <i class="ri-eye-line"></i>
                            </button>

                            @if($journal->status === 'draft')
                            <!-- Edit -->
                            <button type="button" class="btn btn-outline-primary"
                                wire:click="$dispatch('editAdjustment', { journalId: {{ $journal->id }} })"
                                title="Edit Jurnal Penyesuaian">
                                <i class="ri-edit-line"></i>
                            </button>

                            <!-- Posting -->
                            @if($journal->is_balanced)
                            <button type="button" class="btn btn-outline-success"
                                onclick="confirmPostAdjustment('{{ $journal->journal_no }}', {{ $journal->id }})"
                                title="Posting Jurnal Penyesuaian">
                                <i class="ri-check-line"></i>
                            </button>
                            @endif

                            <!-- Hapus -->
                            <button type="button" class="btn btn-outline-danger"
                                onclick="confirmDeleteAdjustment('{{ $journal->journal_no }}', {{ $journal->id }})"
                                title="Hapus Jurnal Penyesuaian">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="ri-file-edit-line display-4 text-muted mb-3"></i>
                            <h5 class="text-muted mb-2">Tidak ada Jurnal Penyesuaian ditemukan</h5>
                            <p class="text-muted mb-0">
                                @if ($search || $filterStatus || $filterPeriod || $dateFrom || $dateTo)
                                Coba sesuaikan kriteria pencarian atau filter Anda
                                @else
                                Buat jurnal penyesuaian pertama Anda untuk memulai
                                @endif
                            </p>
                            @if (!$search && !$filterStatus && !$filterPeriod && !$dateFrom && !$dateTo)
                            <button class="btn btn-primary mt-3" wire:click="$dispatch('openAdjustmentModal')">
                                <i class="ri-add-line"></i> Tambah Jurnal Penyesuaian
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($journals->hasPages())
    <div class="d-flex justify-content-between align-items-center p-3 border-top">
        <div class="text-muted small">
            Menampilkan {{ $journals->firstItem() }} sampai {{ $journals->lastItem() }} dari {{ $journals->total() }}
            hasil
        </div>
        <div>
            {{ $journals->links() }}
        </div>
    </div>
    @endif
</div>
