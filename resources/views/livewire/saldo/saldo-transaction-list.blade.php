<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Transaksi</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Nama customer, telepon...">
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
                    <label class="form-label small text-muted mb-1">Produk</label>
                    <select class="form-select form-select-sm" wire:model.live="filterProduct">
                        <option value="">Semua Produk</option>
                        @foreach($availableProducts as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSaldoTransactionModal')">
                        <i class="ri-add-line"></i> Tambah Transaksi
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
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('transaction_date')">
                            Tanggal
                            @if($sortField === 'transaction_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%">Penyedia</th>
                        <th width="12%">Produk</th>
                        <th width="12%">Customer</th>
                        <th width="11%" class="text-end">Modal</th>
                        <th width="11%" class="text-end" style="cursor:pointer" wire:click="sortBy('sell_price')">
                            Harga Jual
                            @if($sortField === 'sell_price')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%" class="text-end" style="cursor:pointer" wire:click="sortBy('profit')">
                            Profit
                            @if($sortField === 'profit')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%">Catatan</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $idx => $trx)
                    <tr wire:key="trx-{{ $trx->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>{{ $trx->transaction_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-info bg-opacity-75">{{ $trx->saldoProvider->name }}</span>
                        </td>
                        <td>
                            @if($trx->saldoProduct)
                            <span class="small">{{ $trx->saldoProduct->name }}</span>
                            @else
                            <span class="text-muted small">Custom</span>
                            @endif
                        </td>
                        <td>
                            <div class="small">{{ $trx->customer_name ?? '-' }}</div>
                            @if($trx->customer_phone)
                            <small class="text-muted">{{ $trx->customer_phone }}</small>
                            @endif
                        </td>
                        <td class="text-end text-muted">Rp {{ number_format($trx->buy_price, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($trx->sell_price, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <span class="{{ $trx->profit >= 0 ? 'text-success' : 'text-danger' }} fw-semibold">
                                Rp {{ number_format($trx->profit, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ Str::limit($trx->notes, 20) ?? '-' }}</td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editSaldoTransaction', { id: {{ $trx->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteTransaction({{ $trx->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-exchange-funds-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada data transaksi</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ $transactions->count() }} transaksi</small>
                <div>
                    <small class="text-muted me-3">Omzet: <span class="fw-semibold">Rp {{ number_format($transactions->sum('sell_price'), 0, ',', '.') }}</span></small>
                    <small class="text-success fw-semibold">Profit: Rp {{ number_format($transactions->sum('profit'), 0, ',', '.') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
