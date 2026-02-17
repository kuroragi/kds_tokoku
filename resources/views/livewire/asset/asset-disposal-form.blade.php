<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-danger text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'delete-bin' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Disposal' : 'Catat Disposal Aset' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row g-3">
                            @if($isSuperAdmin)
                            <div class="col-md-6">
                                <label class="form-label">Unit Usaha (filter aset)</label>
                                <select class="form-select" wire:model.live="business_unit_id">
                                    <option value="">-- Semua Unit --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label">Aset <span class="text-danger">*</span></label>
                                <select class="form-select @error('asset_id') is-invalid @enderror" wire:model.live="asset_id" {{ $isEditing ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Aset --</option>
                                    @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}">{{ $asset->code }} — {{ $asset->name }}</option>
                                    @endforeach
                                </select>
                                @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Disposal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('disposal_date') is-invalid @enderror"
                                    wire:model="disposal_date">
                                @error('disposal_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Metode Disposal <span class="text-danger">*</span></label>
                                <select class="form-select @error('disposal_method') is-invalid @enderror" wire:model="disposal_method">
                                    @foreach($disposalMethods as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('disposal_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nilai Disposal <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('disposal_amount') is-invalid @enderror"
                                    wire:model.live="disposal_amount" min="0">
                                @error('disposal_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Info Aset --}}
                        @if($asset_id)
                        <div class="border rounded p-3 mt-3 bg-light">
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Nilai Buku Saat Ini</small>
                                    <span class="fw-bold text-primary">Rp {{ number_format($book_value, 0, ',', '.') }}</span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Nilai Disposal</small>
                                    <span class="fw-bold">Rp {{ number_format($disposal_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="col-md-4">
                                    <small class="text-muted d-block">Laba / Rugi</small>
                                    <span class="fw-bold {{ $gain_loss >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $gain_loss >= 0 ? 'Laba' : 'Rugi' }}: Rp {{ number_format(abs($gain_loss), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Info Pembeli</label>
                                <input type="text" class="form-control @error('buyer_info') is-invalid @enderror"
                                    wire:model="buyer_info" placeholder="Nama pembeli / penerima">
                                @error('buyer_info') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Alasan</label>
                                <input type="text" class="form-control @error('reason') is-invalid @enderror"
                                    wire:model="reason" placeholder="Alasan disposal">
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan tambahan"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Jurnal --}}
                        @if(!$isEditing)
                        <div class="border rounded p-3 mt-3 bg-light">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" wire:model="create_journal" id="createDisposalJournal">
                                <label class="form-check-label fw-medium" for="createDisposalJournal">
                                    <i class="ri-file-list-3-line me-1"></i> Buat jurnal disposal otomatis
                                </label>
                            </div>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal">Batal</button>
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="ri-save-line me-1"></i> {{ $isEditing ? 'Perbarui' : 'Simpan Disposal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
