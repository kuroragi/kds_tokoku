<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Produk</label>
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
                    <label class="form-label small text-muted mb-1">Penyedia</label>
                    <select class="form-select form-select-sm" wire:model.live="filterProvider">
                        <option value="">Semua Penyedia</option>
                        @foreach($availableProviders as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
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
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSaldoProductModal')">
                        <i class="ri-add-line"></i> Tambah Produk
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
                        <th width="5%" class="ps-3">#</th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="20%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama Produk
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%">Penyedia</th>
                        <th width="12%" class="text-end" style="cursor:pointer" wire:click="sortBy('buy_price')">
                            Harga Modal
                            @if($sortField === 'buy_price')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%" class="text-end" style="cursor:pointer" wire:click="sortBy('sell_price')">
                            Harga Jual
                            @if($sortField === 'sell_price')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="8%" class="text-end">Margin</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $idx => $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $product->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $product->name }}</div>
                            @if($product->description)
                            <small class="text-muted">{{ Str::limit($product->description, 40) }}</small>
                            @endif
                        </td>
                        <td>
                            @if($product->saldoProvider)
                            <span class="badge bg-info bg-opacity-75">{{ $product->saldoProvider->name }}</span>
                            @else
                            <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-end">Rp {{ number_format($product->buy_price, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($product->sell_price, 0, ',', '.') }}</td>
                        <td class="text-end">
                            @php $margin = $product->sell_price - $product->buy_price; @endphp
                            <span class="{{ $margin >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($margin, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $product->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $product->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editSaldoProduct', { id: {{ $product->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteProduct({{ $product->id }}))"
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
                                <i class="ri-shopping-bag-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada produk saldo</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $products->count() }} produk</small>
        </div>
    </div>
</div>
