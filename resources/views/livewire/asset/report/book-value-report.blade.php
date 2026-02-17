<div>
    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 bg-primary bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h4 class="mb-0 text-primary">{{ $summary['total_assets'] }}</h4>
                    <small class="text-muted">Total Aset Aktif</small>
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
        <div class="col-md-3">
            <div class="card border-0 bg-warning bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h6 class="mb-0 text-warning">Rp {{ number_format($summary['total_accumulated'], 0, ',', '.') }}</h6>
                    <small class="text-muted">Total Akumulasi Penyusutan</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 bg-success bg-opacity-10">
                <div class="card-body py-3 text-center">
                    <h6 class="mb-0 text-success">Rp {{ number_format($summary['total_book_value'], 0, ',', '.') }}</h6>
                    <small class="text-muted">Total Nilai Buku</small>
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
                            placeholder="Kode, nama...">
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
                        <th width="8%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode @if($sortField === 'code') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="15%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama @if($sortField === 'name') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="8%">Kategori</th>
                        @if($isSuperAdmin)
                        <th width="8%">Unit</th>
                        @endif
                        <th width="12%" class="text-end">Harga Perolehan</th>
                        <th width="12%" class="text-end">Akumulasi Penyusutan</th>
                        <th width="12%" class="text-end">Nilai Buku</th>
                        <th width="8%" class="text-end">Nilai Residu</th>
                        <th width="6%">% Susut</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reportData as $idx => $item)
                    <tr>
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $item['asset']->code }}</code></td>
                        <td class="fw-medium">{{ $item['asset']->name }}</td>
                        <td class="text-muted small">{{ $item['asset']->assetCategory->name ?? '-' }}</td>
                        @if($isSuperAdmin)
                        <td class="text-muted small">{{ $item['asset']->businessUnit->name ?? '-' }}</td>
                        @endif
                        <td class="text-end">Rp {{ number_format($item['acquisition_cost'], 0, ',', '.') }}</td>
                        <td class="text-end text-warning">Rp {{ number_format($item['accumulated_depreciation'], 0, ',', '.') }}</td>
                        <td class="text-end fw-medium text-success">Rp {{ number_format($item['book_value'], 0, ',', '.') }}</td>
                        <td class="text-end text-muted">Rp {{ number_format($item['salvage_value'], 0, ',', '.') }}</td>
                        <td>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar bg-{{ $item['depreciation_percent'] > 80 ? 'danger' : ($item['depreciation_percent'] > 50 ? 'warning' : 'success') }}"
                                    style="width: {{ min($item['depreciation_percent'], 100) }}%">
                                    {{ $item['depreciation_percent'] }}%
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 10 : 9 }}" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-bar-chart-box-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Tidak ada data</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($reportData->count() > 0)
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="{{ $isSuperAdmin ? 5 : 4 }}" class="text-end ps-3">Total:</td>
                        <td class="text-end">Rp {{ number_format($summary['total_acquisition'], 0, ',', '.') }}</td>
                        <td class="text-end text-warning">Rp {{ number_format($summary['total_accumulated'], 0, ',', '.') }}</td>
                        <td class="text-end text-success">Rp {{ number_format($summary['total_book_value'], 0, ',', '.') }}</td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Menampilkan {{ $reportData->count() }} aset aktif</small>
        </div>
    </div>
</div>
