<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Invoice, pelanggan...">
                    </div>
                </div>
                @if($isSuperAdmin)
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua</option>
                        @foreach($statuses as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Pembayaran</label>
                    <select class="form-select form-select-sm" wire:model.live="filterPaymentStatus">
                        <option value="">Semua</option>
                        @foreach($paymentStatuses as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-4 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSaleModal')">
                        <i class="ri-add-line"></i> Penjualan Baru
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
                        <th width="13%" style="cursor:pointer" wire:click="sortBy('invoice_number')">
                            Invoice
                            @if($sortField === 'invoice_number')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('sale_date')">
                            Tanggal
                            @if($sortField === 'sale_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="14%">Pelanggan</th>
                        <th width="7%">Jenis</th>
                        <th width="12%" class="text-end">Grand Total</th>
                        <th width="10%" class="text-end">Dibayar</th>
                        <th width="10%" class="text-end">Sisa</th>
                        <th width="8%">Bayar</th>
                        <th width="12%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $idx => $sale)
                    <tr wire:key="sale-{{ $sale->id }}">
                        <td class="ps-3 text-muted">{{ $sales->firstItem() + $idx }}</td>
                        <td><span class="fw-semibold">{{ $sale->invoice_number }}</span></td>
                        <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                        <td>{{ $sale->customer->name }}</td>
                        <td>
                            @php
                                $typeColors = ['goods' => 'secondary', 'saldo' => 'info', 'service' => 'warning', 'mix' => 'dark'];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$sale->sale_type ?? 'goods'] ?? 'secondary' }} bg-opacity-75">
                                {{ $sale->getSaleTypeLabel() }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($sale->grand_total, 0, ',', '.') }}</td>
                        <td class="text-end text-success">Rp {{ number_format($sale->paid_amount, 0, ',', '.') }}</td>
                        <td class="text-end {{ $sale->remaining_amount > 0 ? 'text-danger' : 'text-muted' }}">
                            Rp {{ number_format($sale->remaining_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            @php
                                $pColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
                            @endphp
                            <span class="badge bg-{{ $pColors[$sale->payment_status] ?? 'secondary' }}">
                                {{ $paymentStatuses[$sale->payment_status] ?? $sale->payment_status }}
                            </span>
                            <br>
                            <small class="text-muted">{{ $paymentTypes[$sale->payment_type] ?? '' }}</small>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                @if($sale->payment_status !== 'paid' && $sale->status !== 'cancelled')
                                <button class="btn btn-outline-success btn-sm"
                                    wire:click="$dispatch('openSalePaymentModal', { saleId: {{ $sale->id }} })"
                                    title="Bayar">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </button>
                                @endif
                                @if($sale->status !== 'cancelled')
                                <button class="btn btn-outline-danger btn-sm"
                                    wire:click="cancelSale({{ $sale->id }})"
                                    wire:confirm="Batalkan penjualan ini? Stok akan dikembalikan."
                                    title="Batal">
                                    <i class="ri-close-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ri-shopping-bag-line fs-3 d-block mb-2"></i>
                            Belum ada data penjualan.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($sales->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $sales->links() }}
        </div>
        @endif
    </div>
</div>
