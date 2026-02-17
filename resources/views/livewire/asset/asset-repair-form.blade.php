<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'tools' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Perbaikan' : 'Catat Perbaikan Aset' }}
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
                                <select class="form-select @error('asset_id') is-invalid @enderror" wire:model="asset_id">
                                    <option value="">-- Pilih Aset --</option>
                                    @foreach($assets as $asset)
                                    <option value="{{ $asset->id }}">{{ $asset->code }} — {{ $asset->name }}</option>
                                    @endforeach
                                </select>
                                @error('asset_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vendor Perbaikan</label>
                                <select class="form-select @error('vendor_id') is-invalid @enderror" wire:model="vendor_id">
                                    <option value="">-- Tanpa Vendor --</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Perbaikan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('repair_date') is-invalid @enderror"
                                    wire:model="repair_date">
                                @error('repair_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Biaya <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('cost') is-invalid @enderror"
                                    wire:model="cost" min="0">
                                @error('cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Status</label>
                                <select class="form-select @error('status') is-invalid @enderror" wire:model="status">
                                    @foreach($repairStatuses as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi Perbaikan <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" rows="3" placeholder="Jelaskan kerusakan dan perbaikan..."></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if($status === 'completed')
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" class="form-control @error('completed_date') is-invalid @enderror"
                                    wire:model="completed_date">
                                @error('completed_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @endif

                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan tambahan"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if(!$isEditing)
                            <div class="col-12">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" wire:model="mark_under_repair" id="markUnderRepair">
                                    <label class="form-check-label" for="markUnderRepair">
                                        <i class="ri-alert-line text-warning me-1"></i>
                                        Tandai aset sebagai &quot;Sedang Diperbaiki&quot;
                                    </label>
                                </div>
                            </div>
                            @endif
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
