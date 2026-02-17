<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Aset' : 'Tambah Aset Baru' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        {{-- Unit Usaha & Kategori --}}
                        <h6 class="text-primary mb-3"><i class="ri-building-line"></i> Unit & Kategori</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model.live="business_unit_id">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->first()?->name }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('asset_category_id') is-invalid @enderror" wire:model.live="asset_category_id">
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->code }} — {{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('asset_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vendor</label>
                                <select class="form-select @error('vendor_id') is-invalid @enderror" wire:model="vendor_id">
                                    <option value="">-- Tanpa Vendor --</option>
                                    @foreach($vendors as $vendor)
                                    <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                                    @endforeach
                                </select>
                                @error('vendor_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Informasi Dasar --}}
                        <h6 class="text-primary mb-3"><i class="ri-information-line"></i> Informasi Dasar</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Mis: AST-001">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama aset">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nomor Seri</label>
                                <input type="text" class="form-control @error('serial_number') is-invalid @enderror"
                                    wire:model="serial_number" placeholder="SN / nomor seri">
                                @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" rows="2" placeholder="Deskripsi aset..."></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Keuangan --}}
                        <h6 class="text-primary mb-3"><i class="ri-money-dollar-circle-line"></i> Informasi Keuangan</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Perolehan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('acquisition_date') is-invalid @enderror"
                                    wire:model="acquisition_date">
                                @error('acquisition_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Harga Perolehan <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('acquisition_cost') is-invalid @enderror"
                                    wire:model="acquisition_cost" min="0">
                                @error('acquisition_cost') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Masa Manfaat (bln) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('useful_life_months') is-invalid @enderror"
                                    wire:model="useful_life_months" min="1" max="600">
                                @error('useful_life_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Nilai Residu <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('salvage_value') is-invalid @enderror"
                                    wire:model="salvage_value" min="0">
                                @error('salvage_value') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Metode <span class="text-danger">*</span></label>
                                <select class="form-select @error('depreciation_method') is-invalid @enderror" wire:model="depreciation_method">
                                    <option value="straight_line">Garis Lurus</option>
                                    <option value="declining_balance">Saldo Menurun</option>
                                </select>
                                @error('depreciation_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Lokasi & Kondisi --}}
                        <h6 class="text-primary mb-3"><i class="ri-map-pin-line"></i> Lokasi & Kondisi</h6>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Lokasi</label>
                                <input type="text" class="form-control @error('location') is-invalid @enderror"
                                    wire:model="location" placeholder="Lokasi penempatan aset">
                                @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Kondisi <span class="text-danger">*</span></label>
                                <select class="form-select @error('condition') is-invalid @enderror" wire:model="condition">
                                    @foreach($conditions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('condition') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan tambahan..."></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        {{-- Jurnal Pengadaan --}}
                        @if(!$isEditing)
                        <div class="border rounded p-3 bg-light">
                            <div class="form-check mb-2">
                                <input type="checkbox" class="form-check-input" wire:model.live="create_journal" id="createJournal">
                                <label class="form-check-label fw-medium" for="createJournal">
                                    <i class="ri-file-list-3-line me-1"></i> Buat jurnal pengadaan otomatis
                                </label>
                            </div>
                            @if($create_journal)
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label small">Sumber Pembayaran</label>
                                    <select class="form-select form-select-sm" wire:model="payment_coa_key">
                                        @foreach($paymentOptions as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-8">
                                    <div class="alert alert-info py-2 small mb-0 mt-3">
                                        <i class="ri-information-line me-1"></i>
                                        Jurnal umum akan dibuat: Debit Peralatan, Kredit {{ $paymentOptions[$payment_coa_key] ?? 'Kas' }}
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif
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
