<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Aset</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Kode, nama, lokasi...">
                    </div>
                </div>
                @if($isSuperAdmin)
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openAssetTransferModal')">
                        <i class="ri-add-line"></i> Catat Mutasi
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="3%" class="ps-3">#</th>
                        <th width="10%">Kode Aset</th>
                        <th width="14%">Nama Aset</th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('transfer_date')">
                            Tanggal @if($sortField === 'transfer_date') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="14%">Dari Lokasi</th>
                        <th width="14%">Ke Lokasi</th>
                        <th width="12%">Dari Unit</th>
                        <th width="12%">Ke Unit</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $idx => $transfer)
                    <tr wire:key="trf-{{ $transfer->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $transfer->asset->code ?? '-' }}</code></td>
                        <td>{{ $transfer->asset->name ?? '-' }}</td>
                        <td class="text-muted small">{{ $transfer->transfer_date->format('d/m/Y') }}</td>
                        <td class="text-muted small">{{ $transfer->from_location ?? '-' }}</td>
                        <td class="small fw-medium">{{ $transfer->to_location ?? '-' }}</td>
                        <td class="text-muted small">{{ $transfer->fromBusinessUnit->name ?? '-' }}</td>
                        <td class="small fw-medium">{{ $transfer->toBusinessUnit->name ?? '-' }}</td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editAssetTransfer', { id: {{ $transfer->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteTransfer({{ $transfer->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-arrow-left-right-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada catatan mutasi</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $transfers->count() }} mutasi</small>
        </div>
    </div>
</div>
