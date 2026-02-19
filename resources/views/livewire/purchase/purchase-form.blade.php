<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-shopping-cart-2-line me-1"></i>
                        {{ $purchaseMode === 'direct' ? 'Pembelian Langsung' : 'Pembelian dari PO' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Mode Tabs --}}
                        <ul class="nav nav-tabs mb-3">
                            <li class="nav-item">
                                <a class="nav-link {{ $purchaseMode === 'direct' ? 'active' : '' }}" href="#"
                                   wire:click.prevent="$set('purchaseMode', 'direct')">
                                    <i class="ri-shopping-bag-line me-1"></i> Langsung
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $purchaseMode === 'from_po' ? 'active' : '' }}" href="#"
                                   wire:click.prevent="$set('purchaseMode', 'from_po')">
                                    <i class="ri-truck-line me-1"></i> Dari PO
                                </a>
                            </li>
                        </ul>

                        {{-- Header --}}
                        <div class="row g-3 mb-4">
                            @if($purchaseMode === 'from_po')
                            <div class="col-md-4">
                                <label class="form-label">Purchase Order <span class="text-danger">*</span></label>
                                <select class="form-select @error('purchase_order_id') is-invalid @enderror" wire:model.live="purchase_order_id">
                                    <option value="">-- Pilih PO --</option>
                                    @foreach($availablePOs as $po)
                                    <option value="{{ $po->id }}">{{ $po->po_number }} — {{ $po->vendor->name }}</option>
                                    @endforeach
                                </select>
                                @error('purchase_order_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @endif

                            <div class="col-md-{{ $purchaseMode === 'from_po' ? '4' : '4' }}">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin && $purchaseMode === 'direct')
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model.live="business_unit_id">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->firstWhere('id', $business_unit_id)?->name ?? '-' }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($purchaseMode === 'direct')
                            <div class="col-md-4">
                                <label class="form-label">Vendor <span class="text-danger">*</span></label>
                                <select class="form-select @error('vendor_id') is-invalid @enderror" wire:model="vendor_id">
                                    <option value="">-- Pilih Vendor --</option>
                                    @foreach($availableVendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @else
                            <div class="col-md-4">
                                <label class="form-label">Vendor</label>
                                @php $selectedVendor = $availableVendors->firstWhere('id', $vendor_id); @endphp
                                <input type="text" class="form-control" value="{{ $selectedVendor?->name ?? '-' }}" readonly>
                            </div>
                            @endif

                            <div class="col-md-2">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('purchase_date') is-invalid @enderror" wire:model="purchase_date">
                                @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jatuh Tempo</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" wire:model="due_date">
                                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Items (Direct) --}}
                        @if($purchaseMode === 'direct')
                        <h6 class="border-bottom pb-2 mb-3"><i class="ri-list-check me-1"></i> Item Pembelian</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Barang <span class="text-danger">*</span></th>
                                        <th width="12%">Qty <span class="text-danger">*</span></th>
                                        <th width="18%">Harga Satuan</th>
                                        <th width="15%">Diskon</th>
                                        <th width="15%" class="text-end">Subtotal</th>
                                        <th width="5%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $idx => $item)
                                    <tr wire:key="item-{{ $idx }}">
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <select class="form-select form-select-sm @error('items.'.$idx.'.stock_id') is-invalid @enderror"
                                                wire:model.live="items.{{ $idx }}.stock_id">
                                                <option value="">-- Pilih --</option>
                                                @foreach($availableStocks as $stock)
                                                <option value="{{ $stock->id }}">{{ $stock->code }} - {{ $stock->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01"
                                                class="form-control form-control-sm"
                                                wire:model.live="items.{{ $idx }}.quantity">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" wire:model.live="items.{{ $idx }}.unit_price">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" wire:model.live="items.{{ $idx }}.discount">
                                            </div>
                                        </td>
                                        <td class="text-end fw-semibold">
                                            Rp {{ number_format((($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0)) - ($item['discount'] ?? 0), 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            @if(count($items) > 1)
                                            <button type="button" class="btn btn-outline-danger btn-sm" wire:click="removeItem({{ $idx }})">
                                                <i class="ri-delete-bin-line"></i>
                                            </button>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm mb-3" wire:click="addItem">
                            <i class="ri-add-line"></i> Tambah Item
                        </button>
                        @endif

                        {{-- Items (From PO) --}}
                        @if($purchaseMode === 'from_po' && count($poItems) > 0)
                        <h6 class="border-bottom pb-2 mb-3"><i class="ri-truck-line me-1"></i> Penerimaan Barang dari PO</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="25%">Barang</th>
                                        <th width="10%">Order</th>
                                        <th width="10%">Diterima</th>
                                        <th width="10%">Sisa</th>
                                        <th width="12%">Terima Kali Ini</th>
                                        <th width="13%">Harga</th>
                                        <th width="15%" class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($poItems as $idx => $item)
                                    <tr wire:key="po-item-{{ $idx }}">
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>{{ $item['stock_name'] }}</td>
                                        <td class="text-center">{{ number_format($item['ordered_qty'], 0) }}</td>
                                        <td class="text-center text-muted">{{ number_format($item['received_qty'], 0) }}</td>
                                        <td class="text-center text-warning">{{ number_format($item['remaining_qty'], 0) }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" max="{{ $item['remaining_qty'] }}"
                                                class="form-control form-control-sm"
                                                wire:model.live="poItems.{{ $idx }}.quantity">
                                        </td>
                                        <td class="text-end">Rp {{ number_format($item['unit_price'], 0, ',', '.') }}</td>
                                        <td class="text-end fw-semibold">
                                            Rp {{ number_format(($item['quantity'] ?? 0) * ($item['unit_price'] ?? 0), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif

                        {{-- Payment & Summary --}}
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3"><i class="ri-money-dollar-circle-line me-1"></i> Pembayaran</h6>
                                <div class="mb-3">
                                    <label class="form-label">Tipe Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-select @error('payment_type') is-invalid @enderror" wire:model.live="payment_type">
                                        <option value="cash">Tunai (Bayar Lunas)</option>
                                        <option value="credit">Hutang (Kredit Seluruhnya)</option>
                                        <option value="partial">Bayar Sebagian</option>
                                        <option value="down_payment">Uang Muka (DP)</option>
                                    </select>
                                    @error('payment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                @if($payment_type === 'partial')
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Bayar Sekarang <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('paid_amount') is-invalid @enderror"
                                            wire:model="paid_amount" min="1">
                                    </div>
                                    @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Sisa akan menjadi hutang ke vendor.</small>
                                </div>
                                @endif

                                @if($payment_type === 'down_payment')
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Uang Muka <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('down_payment_amount') is-invalid @enderror"
                                            wire:model="down_payment_amount" min="1">
                                    </div>
                                    @error('down_payment_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">DP mengurangi hutang saat barang diterima. Sisa hutang bisa dibayar kemudian.</small>
                                </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control" wire:model="notes" rows="2" placeholder="Catatan pembelian (opsional)"></textarea>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3"><i class="ri-calculator-line me-1"></i> Ringkasan</h6>
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td class="text-muted">Subtotal</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted">Diskon</span>
                                                <div class="input-group input-group-sm" style="max-width:140px">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" wire:model.live="discount" min="0">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end text-danger">- Rp {{ number_format($discount ?: 0, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted">Pajak</span>
                                                <div class="input-group input-group-sm" style="max-width:140px">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" class="form-control" wire:model.live="tax" min="0">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-end">+ Rp {{ number_format($tax ?: 0, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr class="border-top">
                                        <td class="fw-bold">Grand Total</td>
                                        <td class="text-end fw-bold fs-5 text-success">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($payment_type === 'cash')
                                    <tr><td class="text-muted">Bayar</td><td class="text-end text-success">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Sisa Hutang</td><td class="text-end">Rp 0</td></tr>
                                    @elseif($payment_type === 'credit')
                                    <tr><td class="text-muted">Bayar</td><td class="text-end">Rp 0</td></tr>
                                    <tr><td class="text-muted">Hutang</td><td class="text-end text-danger">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
                                    @elseif($payment_type === 'partial')
                                    <tr><td class="text-muted">Bayar Sekarang</td><td class="text-end text-success">Rp {{ number_format($paid_amount ?: 0, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Sisa Hutang</td><td class="text-end text-danger">Rp {{ number_format(max(0, ($grandTotal) - ($paid_amount ?: 0)), 0, ',', '.') }}</td></tr>
                                    @elseif($payment_type === 'down_payment')
                                    <tr><td class="text-muted">Uang Muka</td><td class="text-end text-success">Rp {{ number_format($down_payment_amount ?: 0, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Sisa Hutang</td><td class="text-end text-danger">Rp {{ number_format(max(0, ($grandTotal) - ($down_payment_amount ?: 0)), 0, ',', '.') }}</td></tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan Pembelian</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
