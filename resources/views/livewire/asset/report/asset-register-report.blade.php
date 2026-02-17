<div>
    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-primary">{{ $summary['total_assets'] }}</h4>
                    <small class="text-muted">Total Aset</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-success">{{ $summary['total_active'] }}</h4>
                    <small class="text-muted">Aktif</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-danger bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-danger">{{ $summary['total_disposed'] }}</h4>
                    <small class="text-muted">Disposed</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-info bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h6 class="mb-0 text-info">Rp {{ number_format($summary['total_acquisition'], 0, ',', '.') }}</h6>
                    <small class="text-muted">Total Harga Perolehan</small>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari</label>
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
                        <option value="">Semua</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua</option>
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Kondisi</label>
                    <select class="form-select form-select-sm" wire:model.live="filterCondition">
                        <option value="">Semua</option>
                        @foreach($conditions as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="3%" class="ps-3">#</th>
                        <th width="7%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode @if($sortField === 'code') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="14%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama @if($sortField === 'name') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="8%">Kategori</th>
                        @if($isSuperAdmin)
                        <th width="8%">Unit</th>
                        @endif
                        <th width="8%">Tgl Perolehan</th>
                        <th width="10%" class="text-end">Harga Perolehan</th>
                        <th width="8%">Lokasi</th>
                        <th width="6%">SN</th>
                        <th width="5%">Kondisi</th>
                        <th width="5%" class="text-center">Status</th>
                        <th width="8%">Vendor</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $idx => $asset)
                    <tr>
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $asset->code }}</code></td>
                        <td class="fw-medium">{{ $asset->name }}</td>
                        <td class="text-muted small">{{ $asset->assetCategory->name ?? '-' }}</td>
                        @if($isSuperAdmin)
                        <td class="text-muted small">{{ $asset->businessUnit->name ?? '-' }}</td>
                        @endif
                        <td class="text-muted small">{{ $asset->acquisition_date->format('d/m/Y') }}</td>
                        <td class="text-end">Rp {{ number_format($asset->acquisition_cost, 0, ',', '.') }}</td>
                        <td class="text-muted small">{{ $asset->location ?? '-' }}</td>
                        <td class="text-muted small">{{ $asset->serial_number ?? '-' }}</td>
                        <td class="small">
                            @php $condColors = ['good' => 'success', 'fair' => 'warning', 'poor' => 'danger']; @endphp
                            <span class="badge bg-{{ $condColors[$asset->condition] ?? 'secondary' }} bg-opacity-75">
                                {{ $conditions[$asset->condition] ?? $asset->condition }}
                            </span>
                        </td>
                        <td class="text-center">
                            @php $statusColors = ['active' => 'success', 'disposed' => 'danger', 'under_repair' => 'warning']; @endphp
                            <span class="badge bg-{{ $statusColors[$asset->status] ?? 'secondary' }} bg-opacity-75">
                                {{ $statuses[$asset->status] ?? $asset->status }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $asset->vendor->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 12 : 11 }}" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-file-list-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Tidak ada data aset</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Menampilkan {{ $assets->count() }} aset</small>
        </div>
    </div>
</div>
