<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Transaksi Saldo' : 'Tambah Transaksi Saldo' }}
                    </h6>
                    <button type="button" class="btn-close btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model.live="business_unit_id">
                                    <option value="">-- Pilih Unit Usaha --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->first()?->code }} — {{ $units->first()?->name }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Penyedia Saldo <span class="text-danger">*</span></label>
                                <select class="form-select @error('saldo_provider_id') is-invalid @enderror" wire:model="saldo_provider_id">
                                    <option value="">-- Pilih Penyedia --</option>
                                    @foreach($availableProviders as $provider)
                                    <option value="{{ $provider->id }}">{{ $provider->name }} (Rp {{ number_format($provider->current_balance, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                @error('saldo_provider_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Produk Saldo</label>
                                <select class="form-select @error('saldo_product_id') is-invalid @enderror" wire:model.live="saldo_product_id">
                                    <option value="">-- Custom / Pilih Produk --</option>
                                    @foreach($availableProducts as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }} (Modal: Rp {{ number_format($product->buy_price, 0, ',', '.') }} | Jual: Rp {{ number_format($product->sell_price, 0, ',', '.') }})</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih produk untuk auto-fill harga, atau kosongkan untuk input manual</small>
                                @error('saldo_product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Modal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('buy_price') is-invalid @enderror"
                                        wire:model="buy_price" min="0" step="1">
                                </div>
                                @error('buy_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('sell_price') is-invalid @enderror"
                                        wire:model="sell_price" min="0" step="1">
                                </div>
                                @error('sell_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @if($buy_price > 0 || $sell_price > 0)
                            <div class="col-md-12">
                                <div class="alert alert-light border py-2 mb-0">
                                    @php $profit = $sell_price - $buy_price; @endphp
                                    <span class="small">Profit: </span>
                                    <span class="fw-semibold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($profit, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            @endif
                            <div class="col-md-4">
                                <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transaction_date') is-invalid @enderror"
                                    wire:model="transaction_date">
                                @error('transaction_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama Customer</label>
                                <input type="text" class="form-control @error('customer_name') is-invalid @enderror"
                                    wire:model="customer_name" placeholder="Nama pembeli (opsional)">
                                @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Telepon</label>
                                <input type="text" class="form-control @error('customer_phone') is-invalid @enderror"
                                    wire:model="customer_phone" placeholder="08xxxx">
                                @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan transaksi (opsional)"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-warning btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
