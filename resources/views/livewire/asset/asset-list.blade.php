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
                            placeholder="Kode, nama, SN, lokasi...">
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
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Kategori</label>
                    <select class="form-select form-select-sm" wire:model.live="filterCategory">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openAssetModal')">
                        <i class="ri-add-line"></i> Tambah Aset
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
                        <th width="8%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode @if($sortField === 'code') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="15%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama @if($sortField === 'name') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="10%">Kategori</th>
                        @if($isSuperAdmin)
                        <th width="9%">Unit</th>
                        @endif
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('acquisition_date')">
                            Tgl Perolehan @if($sortField === 'acquisition_date') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="12%" class="text-end">Harga Perolehan</th>
                        <th width="8%">Lokasi</th>
                        <th width="7%" class="text-center">Status</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $idx => $asset)
                    <tr wire:key="asset-{{ $asset->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $asset->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $asset->name }}</div>
                            @if($asset->serial_number)
                            <small class="text-muted">SN: {{ $asset->serial_number }}</small>
                            @endif
                        </td>
                        <td class="text-muted small">{{ $asset->assetCategory->name ?? '-' }}</td>
                        @if($isSuperAdmin)
                        <td class="text-muted small">{{ $asset->businessUnit->name ?? '-' }}</td>
                        @endif
                        <td class="text-muted small">{{ $asset->acquisition_date->format('d/m/Y') }}</td>
                        <td class="text-end">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                        <td class="text-muted small">{{ $asset->location ?? '-' }}</td>
                        <td class="text-center">
                            @php
                                $statusColors = ['active' => 'success', 'disposed' => 'danger', 'under_repair' => 'warning'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$asset->status] ?? 'secondary' }} bg-opacity-75">
                                {{ $statuses[$asset->status] ?? $asset->status }}
                            </span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editAsset', { id: {{ $asset->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteAsset({{ $asset->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 10 : 9 }}" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-computer-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada aset</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $assets->count() }} aset</small>
        </div>
    </div>
</div>
