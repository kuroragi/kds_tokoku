<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-money-dollar-circle-line me-1"></i> Pembayaran Pembelian
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Purchase Info --}}
                        @if($purchaseInfo)
                        <div class="alert alert-light border mb-3">
                            <div class="row small">
                                <div class="col-6"><strong>No. Invoice:</strong> {{ $purchaseInfo->invoice_number }}</div>
                                <div class="col-6"><strong>Vendor:</strong> {{ $purchaseInfo->vendor->name }}</div>
                                <div class="col-6 mt-1"><strong>Grand Total:</strong> Rp {{ number_format($purchaseInfo->grand_total, 0, ',', '.') }}</div>
                                <div class="col-6 mt-1"><strong>Sudah Dibayar:</strong> Rp {{ number_format($purchaseInfo->paid_amount, 0, ',', '.') }}</div>
                                <div class="col-12 mt-1">
                                    <strong>Sisa Hutang:</strong>
                                    <span class="text-danger fw-bold">Rp {{ number_format($purchaseInfo->remaining_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                    wire:model="amount" min="1" max="{{ $purchaseInfo?->remaining_amount ?? 0 }}">
                            </div>
                            @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @if($purchaseInfo)
                            <small class="text-muted">Maks: Rp {{ number_format($purchaseInfo->remaining_amount, 0, ',', '.') }}</small>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                                wire:model="payment_date">
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" wire:model.live="payment_method">
                                @foreach($methods as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sumber Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_source') is-invalid @enderror" wire:model="payment_source">
                                <option value="kas_utama">Kas Utama</option>
                                <option value="kas_kecil">Kas Kecil</option>
                                <option value="bank_utama">Bank Utama</option>
                            </select>
                            @error('payment_source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Akun yang akan digunakan di jurnal.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. Referensi</label>
                            <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                                wire:model="reference_no" placeholder="No. transfer / giro (opsional)">
                            @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" wire:model="notes" rows="2" placeholder="Catatan pembayaran (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-money-dollar-circle-line"></i> Bayar</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
