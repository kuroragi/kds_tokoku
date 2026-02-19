<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning text-dark py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Transfer Dana' : 'Tambah Transfer Dana' }}
                    </h6>
                    <button type="button" class="btn-close btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Unit Usaha --}}
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
                                <label class="form-label">Tanggal Transfer <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transfer_date') is-invalid @enderror"
                                    wire:model="transfer_date">
                                @error('transfer_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Source --}}
                            <div class="col-md-6">
                                <label class="form-label">Tipe Sumber <span class="text-danger">*</span></label>
                                <select class="form-select @error('source_type') is-invalid @enderror" wire:model.live="source_type">
                                    <option value="cash">Kas</option>
                                    <option value="bank">Bank</option>
                                </select>
                                @error('source_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                @if($source_type === 'bank')
                                <label class="form-label">Rekening Sumber <span class="text-danger">*</span></label>
                                <select class="form-select @error('source_bank_account_id') is-invalid @enderror" wire:model.live="source_bank_account_id">
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($sourceAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->bank->name }} - {{ $account->account_number }} ({{ $account->account_name }})</option>
                                    @endforeach
                                </select>
                                @error('source_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @else
                                <label class="form-label">Sumber</label>
                                <input type="text" class="form-control" value="Kas Utama" readonly>
                                @endif
                            </div>

                            {{-- Destination --}}
                            <div class="col-md-6">
                                <label class="form-label">Tipe Tujuan <span class="text-danger">*</span></label>
                                <select class="form-select @error('destination_type') is-invalid @enderror" wire:model.live="destination_type">
                                    <option value="cash">Kas</option>
                                    <option value="bank">Bank</option>
                                </select>
                                @error('destination_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                @if($destination_type === 'bank')
                                <label class="form-label">Rekening Tujuan <span class="text-danger">*</span></label>
                                <select class="form-select @error('destination_bank_account_id') is-invalid @enderror" wire:model.live="destination_bank_account_id">
                                    <option value="">-- Pilih Rekening --</option>
                                    @foreach($destinationAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->bank->name }} - {{ $account->account_number }} ({{ $account->account_name }})</option>
                                    @endforeach
                                </select>
                                @error('destination_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @else
                                <label class="form-label">Tujuan</label>
                                <input type="text" class="form-control" value="Kas Utama" readonly>
                                @endif
                            </div>

                            {{-- Balance Info --}}
                            @if($sourceBalance > 0 || $source_bank_account_id || $source_type === 'cash')
                            <div class="col-md-12">
                                <div class="alert alert-light border py-2 mb-0">
                                    <span class="small">Saldo sumber saat ini: </span>
                                    <span class="fw-semibold {{ $sourceBalance >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($sourceBalance, 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                            @endif

                            {{-- Amount & Fee --}}
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Transfer <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                        wire:model="amount" min="1" step="1">
                                </div>
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Biaya Admin</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('admin_fee') is-invalid @enderror"
                                        wire:model="admin_fee" min="0" step="1">
                                </div>
                                <small class="text-muted">Auto-fill dari fee matrix (bisa di-override)</small>
                                @error('admin_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Referensi</label>
                                <input type="text" class="form-control @error('reference_no') is-invalid @enderror"
                                    wire:model="reference_no" placeholder="Opsional">
                                @error('reference_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Total Info --}}
                            @if($amount > 0)
                            <div class="col-md-12">
                                <div class="alert alert-warning border py-2 mb-0">
                                    @php $totalDeducted = $amount + $admin_fee; @endphp
                                    <span class="small">Total dipotong dari sumber: </span>
                                    <span class="fw-semibold text-danger">Rp {{ number_format($totalDeducted, 0, ',', '.') }}</span>
                                    <span class="text-muted small ms-2">(Transfer: Rp {{ number_format($amount, 0, ',', '.') }} + Admin: Rp {{ number_format($admin_fee, 0, ',', '.') }})</span>
                                </div>
                            </div>
                            @endif

                            <div class="col-md-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan transfer (opsional)"></textarea>
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
