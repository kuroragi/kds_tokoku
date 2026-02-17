<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-md modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-money-dollar-circle-line me-1"></i> Penerimaan Piutang
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        @if($receivable_info)
                        <div class="alert alert-light border mb-3 py-2">
                            <div class="row g-1">
                                <div class="col-6">
                                    <small class="text-muted d-block">No. Faktur</small>
                                    <strong>{{ $receivable_info['invoice_number'] }}</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted d-block">Pelanggan</small>
                                    <strong>{{ $receivable_info['customer_name'] }}</strong>
                                </div>
                                <div class="col-4 mt-2">
                                    <small class="text-muted d-block">Total</small>
                                    <span>Rp {{ number_format($receivable_info['amount'], 0, ',', '.') }}</span>
                                </div>
                                <div class="col-4 mt-2">
                                    <small class="text-muted d-block">Sudah Diterima</small>
                                    <span class="text-success">Rp {{ number_format($receivable_info['paid_amount'], 0, ',', '.') }}</span>
                                </div>
                                <div class="col-4 mt-2">
                                    <small class="text-muted d-block">Sisa</small>
                                    <strong class="text-danger">Rp {{ number_format($receivable_info['remaining'], 0, ',', '.') }}</strong>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Terima <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('payment_date') is-invalid @enderror"
                                    wire:model="payment_date">
                                @error('payment_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                    wire:model="amount" min="1" max="{{ $receivable_info['remaining'] ?? 0 }}">
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Akun Penerimaan (Kas/Bank) <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_coa_id') is-invalid @enderror"
                                    wire:model="payment_coa_id">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($paymentCoaOptions as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->code }} — {{ $coa->name }}</option>
                                    @endforeach
                                </select>
                                @error('payment_coa_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Referensi</label>
                                <input type="text" class="form-control @error('reference') is-invalid @enderror"
                                    wire:model="reference" placeholder="No. bukti transfer / kwitansi">
                                @error('reference') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan tambahan"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-money-dollar-circle-line"></i> Terima</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Memproses...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
