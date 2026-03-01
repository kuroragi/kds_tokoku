<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Kategori Aset' : 'Tambah Kategori Aset' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row g-3">
                            @if(!$isEditing)
                            <div class="col-md-6">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model="business_unit_id">
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
                            @endif
                            <div class="col-md-{{ $isEditing ? '6' : '6' }}">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Mis: KAT-001">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama kategori">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Deskripsi</label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" placeholder="Deskripsi singkat">
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Masa Manfaat (bulan) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('useful_life_months') is-invalid @enderror"
                                    wire:model="useful_life_months" min="1" max="600">
                                @error('useful_life_months') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                @if($useful_life_months)
                                <small class="text-muted">≈ {{ round($useful_life_months/12, 1) }} tahun</small>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Metode Penyusutan <span class="text-danger">*</span></label>
                                <select class="form-select @error('depreciation_method') is-invalid @enderror" wire:model="depreciation_method">
                                    @foreach($methods as $key => $label)
                                    <option value="{{ $key }}">{{ $label === 'straight_line' || $key === 'straight_line' ? 'Garis Lurus' : 'Saldo Menurun' }}</option>
                                    @endforeach
                                </select>
                                @error('depreciation_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mt-4">
                                    <input type="checkbox" class="form-check-input" wire:model="is_active" id="catActive">
                                    <label class="form-check-label" for="catActive">Aktif</label>
                                </div>
                            </div>
                        </div>

                        {{-- COA Mapping per Kategori --}}
                        <hr class="my-3">
                        <h6 class="text-primary mb-3"><i class="ri-links-line"></i> Mapping Akun COA</h6>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="ri-information-line me-1"></i>
                            Mapping ini menentukan akun COA mana yang digunakan untuk jurnal otomatis (pengadaan, penyusutan, disposal) aset pada kategori ini.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Preset Cepat</label>
                                <select class="form-select form-select-sm" wire:model.live="coa_preset">
                                    <option value="">-- Pilih Preset (opsional) --</option>
                                    @foreach($coaPresets as $key => $preset)
                                    <option value="{{ $key }}">{{ ucfirst($key) }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Pilih preset untuk mengisi mapping COA secara otomatis</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun Aset <span class="text-primary"><i class="ri-arrow-right-up-line"></i></span></label>
                                <input type="text" class="form-control form-control-sm @error('coa_asset_key') is-invalid @enderror"
                                    wire:model="coa_asset_key" placeholder="cth: aset_kendaraan" readonly style="background: #f8f9fa;">
                                @error('coa_asset_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">COA yang di-debit saat pengadaan</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun Akum. Penyusutan <span class="text-warning"><i class="ri-arrow-right-down-line"></i></span></label>
                                <input type="text" class="form-control form-control-sm @error('coa_accumulated_dep_key') is-invalid @enderror"
                                    wire:model="coa_accumulated_dep_key" placeholder="cth: akum_peny_kendaraan" readonly style="background: #f8f9fa;">
                                @error('coa_accumulated_dep_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">COA yang di-kredit saat penyusutan</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun Beban Penyusutan <span class="text-danger"><i class="ri-arrow-left-up-line"></i></span></label>
                                <input type="text" class="form-control form-control-sm @error('coa_expense_dep_key') is-invalid @enderror"
                                    wire:model="coa_expense_dep_key" placeholder="cth: beban_peny_kendaraan" readonly style="background: #f8f9fa;">
                                @error('coa_expense_dep_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">COA yang di-debit saat penyusutan</small>
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
