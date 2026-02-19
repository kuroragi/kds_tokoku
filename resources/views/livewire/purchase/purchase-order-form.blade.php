<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Purchase Order' : 'Buat Purchase Order' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Header --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model.live="business_unit_id">
                                    <option value="">-- Pilih Unit Usaha --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->first()?->name }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
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
                            <div class="col-md-2">
                                <label class="form-label">Tanggal PO <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('po_date') is-invalid @enderror" wire:model="po_date">
                                @error('po_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tgl. Diharapkan</label>
                                <input type="date" class="form-control @error('expected_date') is-invalid @enderror" wire:model="expected_date">
                                @error('expected_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Items --}}
                        <h6 class="border-bottom pb-2 mb-3"><i class="ri-list-check me-1"></i> Item Pembelian</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-bordered table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Barang <span class="text-danger">*</span></th>
                                        <th width="12%">Qty <span class="text-danger">*</span></th>
                                        <th width="18%">Harga Satuan <span class="text-danger">*</span></th>
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
                                                class="form-control form-control-sm @error('items.'.$idx.'.quantity') is-invalid @enderror"
                                                wire:model.live="items.{{ $idx }}.quantity">
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" step="1" min="0"
                                                    class="form-control @error('items.'.$idx.'.unit_price') is-invalid @enderror"
                                                    wire:model.live="items.{{ $idx }}.unit_price">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" step="1" min="0"
                                                    class="form-control"
                                                    wire:model.live="items.{{ $idx }}.discount">
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

                        {{-- Summary --}}
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" wire:model="notes" rows="2" placeholder="Catatan PO (opsional)"></textarea>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-sm mb-0">
                                    <tr>
                                        <td class="text-muted">Subtotal</td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="text-muted">Diskon</span>
                                                <div class="input-group input-group-sm" style="max-width:150px">
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
                                                <div class="input-group input-group-sm" style="max-width:150px">
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
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan PO</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
