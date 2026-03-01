<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Kategori Stok' : 'Tambah Kategori Stok' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
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
                                <label class="form-label">Tipe <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" wire:model="type">
                                    <option value="">-- Pilih Tipe --</option>
                                    @foreach(\App\Models\StockCategory::getTypes() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Kode kategori">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama kategori">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" rows="2" placeholder="Deskripsi kategori (opsional)"></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="categoryIsActive">
                                    <label class="form-check-label" for="categoryIsActive">
                                        {{ $is_active ? 'Aktif' : 'Non-aktif' }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        {{-- COA Mapping per Kategori --}}
                        <hr class="my-3">
                        <h6 class="text-primary mb-3"><i class="ri-links-line"></i> Mapping Akun COA</h6>
                        <div class="alert alert-info py-2 small mb-3">
                            <i class="ri-information-line me-1"></i>
                            Mapping ini menentukan akun COA mana yang digunakan untuk jurnal otomatis (pembelian, penjualan, HPP) stok pada kategori ini.
                            Preset otomatis terisi saat memilih tipe kategori.
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
                                <label class="form-label">Akun Persediaan <span class="text-primary"><i class="ri-archive-line"></i></span></label>
                                <input type="text" class="form-control form-control-sm @error('coa_inventory_key') is-invalid @enderror"
                                    wire:model="coa_inventory_key" placeholder="cth: persediaan_barang" readonly style="background: #f8f9fa;">
                                @error('coa_inventory_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">COA yang di-debit saat pembelian</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun HPP <span class="text-danger"><i class="ri-money-dollar-circle-line"></i></span></label>
                                <input type="text" class="form-control form-control-sm @error('coa_hpp_key') is-invalid @enderror"
                                    wire:model="coa_hpp_key" placeholder="cth: hpp" readonly style="background: #f8f9fa;">
                                @error('coa_hpp_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">COA yang di-debit saat penjualan (COGS)</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun Pendapatan <span class="text-success"><i class="ri-arrow-right-up-line"></i></span></label>
                                <input type="text" class="form-control form-control-sm @error('coa_revenue_key') is-invalid @enderror"
                                    wire:model="coa_revenue_key" placeholder="cth: pendapatan_utama" readonly style="background: #f8f9fa;">
                                @error('coa_revenue_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">COA yang di-kredit saat penjualan</small>
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
