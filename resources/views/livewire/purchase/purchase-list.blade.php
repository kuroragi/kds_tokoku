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
                            placeholder="Invoice, vendor...">
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
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-success" wire:click="$dispatch('openPurchaseModal')">
                            <i class="ri-add-line"></i> Beli Langsung
                        </button>
                        <button class="btn btn-outline-primary" wire:click="$dispatch('openPurchaseFromPO')">
                            <i class="ri-truck-line"></i> Dari PO
                        </button>
                    </div>
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
                        <th width="12%" style="cursor:pointer" wire:click="sortBy('invoice_number')">
                            Invoice
                            @if($sortField === 'invoice_number')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('purchase_date')">
                            Tanggal
                            @if($sortField === 'purchase_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="14%">Vendor</th>
                        <th width="8%">Tipe</th>
                        <th width="7%">Jenis</th>
                        <th width="12%" class="text-end">Grand Total</th>
                        <th width="10%" class="text-end">Dibayar</th>
                        <th width="10%" class="text-end">Sisa</th>
                        <th width="8%">Bayar</th>
                        <th width="12%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $idx => $purchase)
                    <tr wire:key="purchase-{{ $purchase->id }}">
                        <td class="ps-3 text-muted">{{ $purchases->firstItem() + $idx }}</td>
                        <td>
                            <span class="fw-semibold">{{ $purchase->invoice_number }}</span>
                            @if($purchase->purchase_order_id)
                            <br><small class="text-muted">PO: {{ $purchase->purchaseOrder?->po_number }}</small>
                            @endif
                        </td>
                        <td>{{ $purchase->purchase_date->format('d/m/Y') }}</td>
                        <td>{{ $purchase->vendor->name }}</td>
                        <td>
                            <span class="badge bg-{{ $purchase->isDirect() ? 'info' : 'primary' }} bg-opacity-75">
                                {{ $purchase->getTypeLabel() }}
                            </span>
                        </td>
                        <td>
                            @php
                                $typeColors = ['goods' => 'secondary', 'saldo' => 'info', 'service' => 'warning', 'mix' => 'dark'];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$purchase->purchase_type ?? 'goods'] ?? 'secondary' }} bg-opacity-75">
                                {{ $purchase->getPurchaseTypeLabel() }}
                            </span>
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($purchase->grand_total, 0, ',', '.') }}</td>
                        <td class="text-end text-success">Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</td>
                        <td class="text-end {{ $purchase->remaining_amount > 0 ? 'text-danger' : 'text-muted' }}">
                            Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}
                        </td>
                        <td>
                            @php
                                $pColors = ['unpaid' => 'danger', 'partial' => 'warning', 'paid' => 'success'];
                            @endphp
                            <span class="badge bg-{{ $pColors[$purchase->payment_status] ?? 'secondary' }}">
                                {{ $paymentStatuses[$purchase->payment_status] ?? $purchase->payment_status }}
                            </span>
                            <br>
                            <small class="text-muted">{{ $paymentTypes[$purchase->payment_type] ?? '' }}</small>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                @if($purchase->payment_status !== 'paid' && $purchase->status !== 'cancelled')
                                <button class="btn btn-outline-success btn-sm"
                                    wire:click="$dispatch('openPurchasePaymentModal', { purchaseId: {{ $purchase->id }} })"
                                    title="Bayar">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </button>
                                @endif
                                @if($purchase->status !== 'cancelled')
                                <button class="btn btn-outline-danger btn-sm"
                                    wire:click="cancelPurchase({{ $purchase->id }})"
                                    wire:confirm="Batalkan pembelian ini? Stok akan dikembalikan."
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
                            <i class="ri-shopping-cart-2-line fs-3 d-block mb-2"></i>
                            Belum ada data pembelian.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($purchases->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $purchases->links() }}
        </div>
        @endif
    </div>
</div>
