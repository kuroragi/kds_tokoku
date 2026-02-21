<div>
    {{-- Filter --}}
    <div class="card-body border-bottom py-3">
        <div class="row align-items-end g-3">
            <div class="col-md-3">
                <label class="form-label small mb-1">Pencarian</label>
                <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Cari unit usaha / periode...">
            </div>
            @if($isSuperAdmin)
            <div class="col-md-3">
                <label class="form-label small mb-1">Unit Usaha</label>
                <select class="form-select form-select-sm" wire:model.live="filterUnit">
                    <option value="">-- Semua Unit --</option>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <label class="form-label small mb-1">Status</label>
                <select class="form-select form-select-sm" wire:model.live="filterStatus">
                    <option value="">-- Semua --</option>
                    <option value="draft">Draft</option>
                    <option value="posted">Diposting</option>
                </select>
            </div>
            <div class="col-md-2 ms-auto text-end">
                <button class="btn btn-primary btn-sm" wire:click="$dispatch('openOpeningBalanceModal')">
                    <i class="ri-add-line"></i> Buat Saldo Awal
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="4%">#</th>
                        <th>Unit Usaha</th>
                        <th>Periode</th>
                        <th>Tanggal</th>
                        <th class="text-end">Total Debit</th>
                        <th class="text-end">Total Credit</th>
                        <th class="text-center">Entri</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Jurnal</th>
                        <th width="15%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($openingBalances as $idx => $ob)
                    <tr>
                        <td class="text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <span class="badge bg-secondary me-1">{{ $ob->businessUnit?->code }}</span>
                            {{ $ob->businessUnit?->name }}
                        </td>
                        <td>{{ $ob->period?->name ?? '-' }}</td>
                        <td>{{ $ob->balance_date->format('d/m/Y') }}</td>
                        <td class="text-end">Rp {{ number_format($ob->total_debit, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($ob->total_credit, 0, ',', '.') }}</td>
                        <td class="text-center"><span class="badge bg-info">{{ $ob->entries_count }}</span></td>
                        <td class="text-center">
                            @if($ob->status === 'posted')
                            <span class="badge bg-success"><i class="ri-check-line"></i> Diposting</span>
                            @else
                            <span class="badge bg-warning text-dark"><i class="ri-draft-line"></i> Draft</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($ob->journal_master_id)
                            <span class="badge bg-primary">{{ $ob->journalMaster?->journal_no ?? '-' }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                @if($ob->status === 'draft')
                                <button class="btn btn-outline-primary" wire:click="editBalance({{ $ob->id }})" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-success" wire:click="postBalance({{ $ob->id }})"
                                    wire:confirm="Posting saldo awal ini ke jurnal? Pastikan saldo sudah balance." title="Posting">
                                    <i class="ri-send-plane-line"></i>
                                </button>
                                <button class="btn btn-outline-danger" wire:click="deleteBalance({{ $ob->id }})"
                                    wire:confirm="Hapus saldo awal ini?" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @else
                                <button class="btn btn-outline-warning" wire:click="unpostBalance({{ $ob->id }})"
                                    wire:confirm="Batalkan posting saldo awal ini? Jurnal terkait akan dihapus." title="Unpost">
                                    <i class="ri-arrow-go-back-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                            Belum ada saldo awal. Klik "Buat Saldo Awal" untuk memulai.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
