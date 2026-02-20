<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-xl" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-shopping-bag-line me-1"></i> Penjualan Baru
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Header --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
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

                            <div class="col-md-3">
                                <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                                <select class="form-select @error('customer_id') is-invalid @enderror" wire:model="customer_id">
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($availableCustomers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Jenis Penjualan <span class="text-danger">*</span></label>
                                <select class="form-select @error('sale_type') is-invalid @enderror" wire:model.live="sale_type">
                                    @foreach($saleTypes as $val => $label)
                                    <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('sale_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-2">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('sale_date') is-invalid @enderror" wire:model="sale_date">
                                @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Jatuh Tempo</label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror" wire:model="due_date">
                                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Items --}}
                        <h6 class="border-bottom pb-2 mb-3"><i class="ri-list-check me-1"></i> Item Penjualan</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="4%">#</th>
                                        @if($sale_type === 'mix')
                                        <th width="10%">Jenis</th>
                                        @endif
                                        <th width="{{ $sale_type === 'mix' ? '25%' : '30%' }}">
                                            @if($sale_type === 'goods') Barang
                                            @elseif($sale_type === 'saldo') Provider Saldo
                                            @elseif($sale_type === 'service') Deskripsi Jasa
                                            @else Item
                                            @endif
                                            <span class="text-danger">*</span>
                                        </th>
                                        <th width="10%">Qty <span class="text-danger">*</span></th>
                                        <th width="15%">Harga Jual</th>
                                        <th width="12%">Diskon</th>
                                        <th width="14%" class="text-end">Subtotal</th>
                                        <th width="4%"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($items as $idx => $item)
                                    @php $currentItemType = $item['item_type'] ?? 'goods'; @endphp
                                    <tr wire:key="item-{{ $idx }}">
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>

                                        @if($sale_type === 'mix')
                                        <td>
                                            <select class="form-select form-select-sm" wire:model.live="items.{{ $idx }}.item_type">
                                                @foreach($itemTypes as $typeVal => $typeLabel)
                                                <option value="{{ $typeVal }}">{{ $typeLabel }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        @endif

                                        <td>
                                            @if($currentItemType === 'goods')
                                            <select class="form-select form-select-sm @error('items.'.$idx.'.stock_id') is-invalid @enderror"
                                                wire:model.live="items.{{ $idx }}.stock_id">
                                                <option value="">-- Pilih Barang --</option>
                                                @foreach($availableStocks as $stock)
                                                <option value="{{ $stock->id }}">{{ $stock->code }} - {{ $stock->name }} (Stok: {{ number_format($stock->current_stock, 0) }})</option>
                                                @endforeach
                                            </select>
                                            @error('items.'.$idx.'.stock_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            @elseif($currentItemType === 'saldo')
                                            <select class="form-select form-select-sm @error('items.'.$idx.'.saldo_provider_id') is-invalid @enderror"
                                                wire:model.live="items.{{ $idx }}.saldo_provider_id">
                                                <option value="">-- Pilih Provider --</option>
                                                @foreach($availableSaldoProviders as $provider)
                                                <option value="{{ $provider->id }}">{{ $provider->code }} - {{ $provider->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('items.'.$idx.'.saldo_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            <input type="text" class="form-control form-control-sm mt-1 @error('items.'.$idx.'.description') is-invalid @enderror"
                                                wire:model="items.{{ $idx }}.description" placeholder="Keterangan saldo...">
                                            @error('items.'.$idx.'.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            @elseif($currentItemType === 'service')
                                            <input type="text" class="form-control form-control-sm @error('items.'.$idx.'.description') is-invalid @enderror"
                                                wire:model="items.{{ $idx }}.description" placeholder="Deskripsi jasa...">
                                            @error('items.'.$idx.'.description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                            @endif
                                        </td>

                                        <td>
                                            <input type="number" step="0.01" min="0.01"
                                                class="form-control form-control-sm @error('items.'.$idx.'.quantity') is-invalid @enderror"
                                                wire:model.live="items.{{ $idx }}.quantity">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control @error('items.'.$idx.'.unit_price') is-invalid @enderror"
                                                    wire:model.live="items.{{ $idx }}.unit_price">
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

                        {{-- Payment & Summary --}}
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="border-bottom pb-2 mb-3"><i class="ri-money-dollar-circle-line me-1"></i> Pembayaran</h6>
                                <div class="mb-3">
                                    <label class="form-label">Tipe Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-select @error('payment_type') is-invalid @enderror" wire:model.live="payment_type">
                                        <option value="cash">Tunai (Bayar Lunas)</option>
                                        <option value="credit">Piutang (Kredit Seluruhnya)</option>
                                        <option value="partial">Bayar Sebagian</option>
                                        <option value="down_payment">Uang Muka (DP)</option>
                                        <option value="prepaid_deduction">Potong Pendapatan Diterima Dimuka</option>
                                    </select>
                                    @error('payment_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                @if($payment_type !== 'credit')
                                <div class="mb-3">
                                    <label class="form-label">Sumber Pembayaran <span class="text-danger">*</span></label>
                                    <select class="form-select @error('payment_source') is-invalid @enderror" wire:model="payment_source">
                                        <option value="kas_utama">Kas Utama</option>
                                        <option value="kas_kecil">Kas Kecil</option>
                                        <option value="bank_utama">Bank Utama</option>
                                    </select>
                                    @error('payment_source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                @endif

                                @if($payment_type === 'partial')
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Bayar Sekarang <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('paid_amount') is-invalid @enderror"
                                            wire:model="paid_amount" min="1">
                                    </div>
                                    @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Sisa akan menjadi piutang ke pelanggan.</small>
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
                                    <small class="text-muted">DP mengurangi piutang. Sisa piutang bisa dibayar kemudian.</small>
                                </div>
                                @endif

                                @if($payment_type === 'prepaid_deduction')
                                <div class="mb-3">
                                    <label class="form-label">Jumlah Potong Pendapatan Diterima Dimuka <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('prepaid_deduction_amount') is-invalid @enderror"
                                            wire:model="prepaid_deduction_amount" min="1">
                                    </div>
                                    @error('prepaid_deduction_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Memotong dari pendapatan yang sudah diterima dimuka dari pelanggan.</small>
                                </div>
                                @endif

                                <div class="mb-3">
                                    <label class="form-label">Catatan</label>
                                    <textarea class="form-control" wire:model="notes" rows="2" placeholder="Catatan penjualan (opsional)"></textarea>
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
                                        <td class="text-end fw-bold fs-5 text-primary">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td>
                                    </tr>
                                    @if($payment_type === 'cash')
                                    <tr><td class="text-muted">Diterima</td><td class="text-end text-success">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Piutang</td><td class="text-end">Rp 0</td></tr>
                                    @elseif($payment_type === 'credit')
                                    <tr><td class="text-muted">Diterima</td><td class="text-end">Rp 0</td></tr>
                                    <tr><td class="text-muted">Piutang</td><td class="text-end text-danger">Rp {{ number_format($grandTotal, 0, ',', '.') }}</td></tr>
                                    @elseif($payment_type === 'partial')
                                    <tr><td class="text-muted">Diterima Sekarang</td><td class="text-end text-success">Rp {{ number_format($paid_amount ?: 0, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Piutang</td><td class="text-end text-danger">Rp {{ number_format(max(0, ($grandTotal) - ($paid_amount ?: 0)), 0, ',', '.') }}</td></tr>
                                    @elseif($payment_type === 'down_payment')
                                    <tr><td class="text-muted">Uang Muka</td><td class="text-end text-success">Rp {{ number_format($down_payment_amount ?: 0, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Piutang</td><td class="text-end text-danger">Rp {{ number_format(max(0, ($grandTotal) - ($down_payment_amount ?: 0)), 0, ',', '.') }}</td></tr>
                                    @elseif($payment_type === 'prepaid_deduction')
                                    <tr><td class="text-muted">Potong Pend. Diterima Dimuka</td><td class="text-end text-info">Rp {{ number_format($prepaid_deduction_amount ?: 0, 0, ',', '.') }}</td></tr>
                                    <tr><td class="text-muted">Piutang</td><td class="text-end text-danger">Rp {{ number_format(max(0, ($grandTotal) - ($prepaid_deduction_amount ?: 0)), 0, ',', '.') }}</td></tr>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan Penjualan</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
