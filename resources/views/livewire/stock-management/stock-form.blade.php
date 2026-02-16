<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Stok' : 'Tambah Stok Baru' }}
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
                                <label class="form-label">Grup Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category_group_id') is-invalid @enderror" wire:model="category_group_id"
                                    {{ !$business_unit_id ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Grup --</option>
                                    @foreach($categoryGroups as $group)
                                    <option value="{{ $group->id }}">{{ $group->code }} — {{ $group->name }}</option>
                                    @endforeach
                                </select>
                                @error('category_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Satuan <span class="text-danger">*</span></label>
                                <select class="form-select @error('unit_of_measure_id') is-invalid @enderror" wire:model="unit_of_measure_id"
                                    {{ !$business_unit_id ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Satuan --</option>
                                    @foreach($measures as $measure)
                                    <option value="{{ $measure->id }}">{{ $measure->code }} — {{ $measure->name }}</option>
                                    @endforeach
                                </select>
                                @error('unit_of_measure_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Kode stok">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama stok">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Barcode</label>
                                <input type="text" class="form-control @error('barcode') is-invalid @enderror"
                                    wire:model="barcode" placeholder="Barcode (opsional)">
                                @error('barcode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" rows="2" placeholder="Deskripsi stok (opsional)"></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <hr class="my-1">
                                <h6 class="text-primary mb-0"><i class="ri-money-dollar-circle-line me-1"></i> Harga & Stok</h6>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Harga Beli <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('buy_price') is-invalid @enderror"
                                        wire:model="buy_price" step="0.01" min="0">
                                </div>
                                @error('buy_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Harga Jual <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('sell_price') is-invalid @enderror"
                                        wire:model="sell_price" step="0.01" min="0">
                                </div>
                                @error('sell_price') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Stok Minimal <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('min_stock') is-invalid @enderror"
                                    wire:model="min_stock" step="0.01" min="0">
                                @error('min_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="stockIsActive">
                                    <label class="form-check-label" for="stockIsActive">
                                        {{ $is_active ? 'Aktif' : 'Non-aktif' }}
                                    </label>
                                </div>
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
</div>
