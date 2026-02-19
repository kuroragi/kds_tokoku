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
                        @if($purchase)
                        <div class="alert alert-light border mb-3">
                            <div class="row small">
                                <div class="col-6"><strong>No. Invoice:</strong> {{ $purchase->invoice_number }}</div>
                                <div class="col-6"><strong>Vendor:</strong> {{ $purchase->vendor->name }}</div>
                                <div class="col-6 mt-1"><strong>Grand Total:</strong> Rp {{ number_format($purchase->grand_total, 0, ',', '.') }}</div>
                                <div class="col-6 mt-1"><strong>Sudah Dibayar:</strong> Rp {{ number_format($purchase->paid_amount, 0, ',', '.') }}</div>
                                <div class="col-12 mt-1">
                                    <strong>Sisa Hutang:</strong>
                                    <span class="text-danger fw-bold">Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Jumlah Bayar <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                    wire:model="amount" min="1" max="{{ $purchase?->remaining_amount ?? 0 }}">
                            </div>
                            @error('amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            @if($purchase)
                            <small class="text-muted">Maks: Rp {{ number_format($purchase->remaining_amount, 0, ',', '.') }}</small>
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
                            <select class="form-select @error('payment_method') is-invalid @enderror" wire:model="payment_method">
                                <option value="cash">Tunai</option>
                                <option value="bank_transfer">Transfer Bank</option>
                                <option value="giro">Giro</option>
                                <option value="other">Lainnya</option>
                            </select>
                            @error('payment_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
