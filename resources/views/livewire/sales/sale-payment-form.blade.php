<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-md" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-money-dollar-circle-line me-1"></i> Pembayaran Piutang
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        @if($saleInfo)
                        <div class="alert alert-info py-2 mb-3">
                            <div class="row">
                                <div class="col-6"><small class="text-muted">No. Invoice</small><div class="fw-semibold">{{ $saleInfo->invoice_number }}</div></div>
                                <div class="col-6"><small class="text-muted">Pelanggan</small><div class="fw-semibold">{{ $saleInfo->customer->name ?? '-' }}</div></div>
                            </div>
                            <hr class="my-2">
                            <div class="row">
                                <div class="col-4"><small class="text-muted">Total</small><div class="fw-semibold">Rp {{ number_format($saleInfo->grand_total, 0, ',', '.') }}</div></div>
                                <div class="col-4"><small class="text-muted">Dibayar</small><div class="fw-semibold text-success">Rp {{ number_format($saleInfo->paid_amount, 0, ',', '.') }}</div></div>
                                <div class="col-4"><small class="text-muted">Sisa</small><div class="fw-semibold text-danger">Rp {{ number_format($saleInfo->remaining_amount, 0, ',', '.') }}</div></div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                    wire:model="amount" min="1" max="{{ $saleInfo?->remaining_amount ?? 0 }}">
                            </div>
                            @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Tanggal Bayar <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('payment_date') is-invalid @enderror" wire:model="payment_date">
                            @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran <span class="text-danger">*</span></label>
                            <select class="form-select @error('payment_method') is-invalid @enderror" wire:model.live="payment_method">
                                <option value="cash">Tunai</option>
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="giro">Giro</option>
                                <option value="e_wallet">E-Wallet</option>
                                <option value="other">Lainnya</option>
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Sumber Pembayaran</label>
                            <select class="form-select @error('payment_source') is-invalid @enderror" wire:model="payment_source">
                                <option value="kas_utama">Kas Utama</option>
                                <option value="kas_kecil">Kas Kecil</option>
                                <option value="bank_utama">Bank Utama</option>
                            </select>
                            @error('payment_source') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">No. Referensi</label>
                            <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                                wire:model="reference_no" placeholder="No. transfer / giro (opsional)">
                            @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-check-line"></i> Bayar</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
