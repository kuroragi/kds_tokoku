<div>
    {{-- Header --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <div class="input-group input-group-sm" style="max-width:260px">
                <span class="input-group-text"><i class="ri-search-line"></i></span>
                <input type="text" class="form-control" wire:model.live.debounce.300ms="search" placeholder="Cari no. opname...">
            </div>

            @if($isSuperAdmin)
            <select class="form-select form-select-sm" wire:model.live="filterUnit" style="max-width:180px">
                <option value="">Semua Unit</option>
                @foreach($units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                @endforeach
            </select>
            @endif

            <select class="form-select form-select-sm" wire:model.live="filterStatus" style="max-width:160px">
                <option value="">Semua Status</option>
                <option value="draft">Draft</option>
                <option value="approved">Disetujui</option>
                <option value="cancelled">Dibatalkan</option>
            </select>
        </div>
        <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSaldoOpnameModal')">
            <i class="ri-add-line"></i> Opname Saldo Baru
        </button>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover table-bordered table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th width="5%">#</th>
                    <th class="cursor-pointer" wire:click="sortBy('opname_number')">
                        No. Opname
                        @if($sortField === 'opname_number')
                        <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                        @endif
                    </th>
                    <th>Unit Usaha</th>
                    <th class="cursor-pointer" wire:click="sortBy('opname_date')">
                        Tanggal
                        @if($sortField === 'opname_date')
                        <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                        @endif
                    </th>
                    <th class="text-center">Jumlah Provider</th>
                    <th class="text-end">Total Selisih</th>
                    <th class="text-center">Status</th>
                    <th width="12%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($opnames as $index => $opname)
                <tr>
                    <td class="text-center text-muted">{{ $opnames->firstItem() + $index }}</td>
                    <td class="fw-semibold">{{ $opname->opname_number }}</td>
                    <td>{{ $opname->businessUnit->code ?? '-' }}</td>
                    <td>{{ \Carbon\Carbon::parse($opname->opname_date)->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $opname->details_count ?? $opname->details->count() }}</td>
                    <td class="text-end">
                        @php $diff = $opname->total_difference; @endphp
                        @if($diff > 0)
                        <span class="text-success fw-bold">+Rp {{ number_format($diff, 0, ',', '.') }}</span>
                        @elseif($diff < 0)
                        <span class="text-danger fw-bold">-Rp {{ number_format(abs($diff), 0, ',', '.') }}</span>
                        @else
                        <span class="text-muted">Rp 0</span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($opname->status === 'draft')
                        <span class="badge bg-warning">Draft</span>
                        @elseif($opname->status === 'approved')
                        <span class="badge bg-success">Disetujui</span>
                        @else
                        <span class="badge bg-secondary">Dibatalkan</span>
                        @endif
                    </td>
                    <td>
                        @if($opname->status === 'draft')
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-success" wire:click="approveOpname({{ $opname->id }})"
                                wire:confirm="Setujui opname saldo ini? Saldo provider akan disesuaikan.">
                                <i class="ri-check-double-line"></i>
                            </button>
                            <button class="btn btn-outline-danger" wire:click="cancelOpname({{ $opname->id }})"
                                wire:confirm="Batalkan opname saldo ini?">
                                <i class="ri-close-line"></i>
                            </button>
                            <button class="btn btn-outline-secondary" wire:click="deleteOpname({{ $opname->id }})"
                                wire:confirm="Hapus draft opname saldo ini?">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                        @else
                        <span class="text-muted small">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-4">
                        <i class="ri-inbox-line fs-3 d-block mb-1"></i> Belum ada data saldo opname.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($opnames->hasPages())
    <div class="mt-3">{{ $opnames->links() }}</div>
    @endif
</div>
