<div>
    {{-- Bank Modal --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Bank' : 'Tambah Bank' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeBankModal"></button>
                </div>
                <form wire:submit="saveBank">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Kode Bank <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Contoh: BCA">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Bank <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Contoh: Bank Central Asia">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SWIFT Code</label>
                                <input type="text" class="form-control @error('swift_code') is-invalid @enderror"
                                    wire:model="swift_code" placeholder="Contoh: CENAIDJA">
                                @error('swift_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="bankIsActive">
                                    <label class="form-check-label" for="bankIsActive">
                                        {{ $is_active ? 'Aktif' : 'Non-aktif' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeBankModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Fee Matrix Modal --}}
    @if($showFeeModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditingFee ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditingFee ? 'Edit Fee Matrix' : 'Tambah Fee Matrix' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeFeeModal"></button>
                </div>
                <form wire:submit="saveFee">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bank Asal <span class="text-danger">*</span></label>
                                <select class="form-select @error('source_bank_id') is-invalid @enderror" wire:model="source_bank_id">
                                    <option value="">-- Pilih Bank --</option>
                                    @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->code }} — {{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('source_bank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bank Tujuan <span class="text-danger">*</span></label>
                                <select class="form-select @error('destination_bank_id') is-invalid @enderror" wire:model="destination_bank_id">
                                    <option value="">-- Pilih Bank --</option>
                                    @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->code }} — {{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('destination_bank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tipe Transfer <span class="text-danger">*</span></label>
                                <select class="form-select @error('transfer_type') is-invalid @enderror" wire:model="transfer_type">
                                    @foreach($transferTypes as $type)
                                    <option value="{{ $type }}">{{ strtoupper($type) }}</option>
                                    @endforeach
                                </select>
                                @error('transfer_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Biaya Transfer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('fee') is-invalid @enderror"
                                        wire:model="fee" min="0" step="1">
                                </div>
                                @error('fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Catatan</label>
                                <input type="text" class="form-control @error('fee_notes') is-invalid @enderror"
                                    wire:model="fee_notes" placeholder="Catatan (opsional)">
                                @error('fee_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="fee_is_active" id="feeIsActive">
                                    <label class="form-check-label" for="feeIsActive">
                                        {{ $fee_is_active ? 'Aktif' : 'Non-aktif' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeFeeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-info btn-sm" wire:loading.attr="disabled">
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
