<div>
    {{-- Bank Account Modal --}}
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Rekening Bank' : 'Tambah Rekening Bank' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
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
                                <label class="form-label">Bank <span class="text-danger">*</span></label>
                                <select class="form-select @error('bank_id') is-invalid @enderror" wire:model="bank_id">
                                    <option value="">-- Pilih Bank --</option>
                                    @foreach($availableBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->code }} — {{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('bank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">No. Rekening <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_number') is-invalid @enderror"
                                    wire:model="account_number" placeholder="Contoh: 1234567890">
                                @error('account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama Pemilik <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('account_name') is-invalid @enderror"
                                    wire:model="account_name" placeholder="Nama pemilik rekening">
                                @error('account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Saldo Awal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('initial_balance') is-invalid @enderror"
                                        wire:model="initial_balance" min="0" step="0.01">
                                </div>
                                @error('initial_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="accountIsActive">
                                    <label class="form-check-label" for="accountIsActive">
                                        {{ $is_active ? 'Aktif' : 'Non-aktif' }}
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" rows="2" placeholder="Keterangan rekening (opsional)"></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
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

    {{-- Cash Account Modal --}}
    @if($showCashModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-pencil-line me-1"></i> Edit Kas
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeCashModal"></button>
                </div>
                <form wire:submit="saveCashAccount">
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Kas <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('cash_name') is-invalid @enderror"
                                    wire:model="cash_name" placeholder="Contoh: Kas Utama">
                                @error('cash_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Saldo Awal <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('cash_initial_balance') is-invalid @enderror"
                                        wire:model="cash_initial_balance" min="0" step="0.01">
                                </div>
                                @error('cash_initial_balance') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeCashModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-success btn-sm" wire:loading.attr="disabled">
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
