<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'arrow-left-right' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Mutasi' : 'Catat Mutasi Aset' }}
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
                                <select class="form-select @error('asset_id') is-invalid @enderror" wire:model.live="asset_id">
                                    <option value="">-- Pilih Aset --</option>
                                    @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}">{{ $asset->code }} — {{ $asset->name }}</option>
                                    @endforeach
                                </select>
                                @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tanggal Mutasi <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('transfer_date') is-invalid @enderror"
                                    wire:model="transfer_date">
                                @error('transfer_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-primary mt-3 mb-2"><i class="ri-map-pin-line"></i> Lokasi</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Dari Lokasi</label>
                                <input type="text" class="form-control @error('from_location') is-invalid @enderror"
                                    wire:model="from_location" placeholder="Lokasi asal" readonly>
                                @error('from_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ke Lokasi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('to_location') is-invalid @enderror"
                                    wire:model="to_location" placeholder="Lokasi tujuan">
                                @error('to_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <h6 class="text-primary mt-3 mb-2"><i class="ri-building-line"></i> Unit Usaha (opsional)</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Dari Unit</label>
                                <select class="form-select" wire:model="from_business_unit_id" disabled>
                                    <option value="">-</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Ke Unit</label>
                                <select class="form-select @error('to_business_unit_id') is-invalid @enderror" wire:model="to_business_unit_id">
                                    <option value="">-- Tidak Pindah Unit --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @error('to_business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Alasan</label>
                                <input type="text" class="form-control @error('reason') is-invalid @enderror"
                                    wire:model="reason" placeholder="Alasan mutasi">
                                @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="1" placeholder="Catatan tambahan"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-save-line me-1"></i> {{ $isEditing ? 'Perbarui' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
