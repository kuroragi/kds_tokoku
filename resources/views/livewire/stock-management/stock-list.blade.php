<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Stok</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Kode, nama, barcode...">
                    </div>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Grup Kategori</label>
                    <select class="form-select form-select-sm" wire:model.live="filterCategory">
                        <option value="">Semua Grup</option>
                        @foreach($categoryGroups as $group)
                        <option value="{{ $group->id }}">{{ $group->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-aktif</option>
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openStockModal')">
                        <i class="ri-add-line"></i> Tambah Stok
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
                        <th width="4%" class="ps-3">#</th>
                        <th width="8%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="16%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%">Grup</th>
                        <th width="8%">Satuan</th>
                        <th width="10%" class="text-end">Harga Beli</th>
                        <th width="10%" class="text-end">Harga Jual</th>
                        <th width="8%" class="text-center">Stok</th>
                        <th width="10%">Unit Usaha</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stocks as $idx => $stock)
                    <tr wire:key="stock-{{ $stock->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $stock->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $stock->name }}</div>
                            @if($stock->barcode)
                            <small class="text-muted"><i class="ri-barcode-line"></i> {{ $stock->barcode }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-75">{{ $stock->categoryGroup->name ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-outline-secondary">{{ $stock->unitOfMeasure->symbol ?? $stock->unitOfMeasure->code ?? '-' }}</span>
                        </td>
                        <td class="text-end">{{ number_format($stock->buy_price, 0, ',', '.') }}</td>
                        <td class="text-end">{{ number_format($stock->sell_price, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($stock->isLowStock())
                            <span class="badge bg-danger bg-opacity-75" title="Stok rendah! Min: {{ $stock->min_stock }}">
                                {{ number_format($stock->current_stock, 0, ',', '.') }}
                            </span>
                            @else
                            <span class="badge bg-success bg-opacity-75">
                                {{ number_format($stock->current_stock, 0, ',', '.') }}
                            </span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-75">{{ $stock->businessUnit->name }}</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $stock->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $stock->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editStock', { id: {{ $stock->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteStock({{ $stock->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-shopping-bag-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada stok</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $stocks->count() }} stok</small>
        </div>
    </div>
</div>
