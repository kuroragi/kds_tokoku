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
                            placeholder="Kode, nama aset...">
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
                    <label class="form-label small text-muted mb-1">Periode</label>
                    <select class="form-select form-select-sm" wire:model.live="filterPeriod">
                        <option value="">Semua Periode</option>
                        @foreach($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg text-end">
                    <button class="btn btn-success btn-sm" wire:click="$dispatch('openDepreciationProcess')">
                        <i class="ri-calculator-line"></i> Proses Penyusutan
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
                        <th width="15%">Nama Aset</th>
                        <th width="10%">Kategori</th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('depreciation_date')">
                            Tanggal @if($sortField === 'depreciation_date') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="8%">Periode</th>
                        <th width="12%" class="text-end">Penyusutan</th>
                        <th width="12%" class="text-end">Akumulasi</th>
                        <th width="12%" class="text-end">Nilai Buku</th>
                        <th width="8%">Jurnal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($depreciations as $idx => $dep)
                    <tr wire:key="dep-{{ $dep->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $dep->asset->code ?? '-' }}</code></td>
                        <td>{{ $dep->asset->name ?? '-' }}</td>
                        <td class="text-muted small">{{ $dep->asset->assetCategory->name ?? '-' }}</td>
                        <td class="text-muted small">{{ $dep->depreciation_date->format('d/m/Y') }}</td>
                        <td class="text-muted small">{{ $dep->period->period_name ?? '-' }}</td>
                        <td class="text-end text-danger">Rp {{ number_format($dep->depreciation_amount, 0, ',', '.') }}</td>
                        <td class="text-end text-warning">Rp {{ number_format($dep->accumulated_depreciation, 0, ',', '.') }}</td>
                        <td class="text-end fw-medium">Rp {{ number_format($dep->book_value, 0, ',', '.') }}</td>
                        <td>
                            @if($dep->journalMaster)
                            <span class="badge bg-success bg-opacity-75">{{ $dep->journalMaster->journal_no }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-line-chart-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada catatan penyusutan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $depreciations->count() }} catatan penyusutan</small>
        </div>
    </div>
</div>
