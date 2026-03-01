<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Grup Kategori' : 'Tambah Grup Kategori' }}
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
                                <label class="form-label">Kategori Stok <span class="text-danger">*</span></label>
                                <select class="form-select @error('stock_category_id') is-invalid @enderror" wire:model.live="stock_category_id"
                                    {{ !$business_unit_id ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Kategori --</option>
                                    @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->code }} — {{ $cat->name }}</option>
                                    @endforeach
                                </select>
                                @error('stock_category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Kode grup">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama grup kategori">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <textarea class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" rows="2" placeholder="Deskripsi grup (opsional)"></textarea>
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-12">
                                <hr class="my-1">
                                <h6 class="text-primary mb-0"><i class="ri-money-dollar-circle-line me-1"></i> Pengaturan Akun</h6>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Akun Persediaan</label>
                                <select class="form-select form-select-sm @error('coa_inventory_id') is-invalid @enderror" wire:model="coa_inventory_id">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($inventoryCoas as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->code }} — {{ $coa->name }}</option>
                                    @endforeach
                                </select>
                                @error('coa_inventory_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Tipe: Aktiva</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun Pendapatan</label>
                                <select class="form-select form-select-sm @error('coa_revenue_id') is-invalid @enderror" wire:model="coa_revenue_id">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($revenueCoas as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->code }} — {{ $coa->name }}</option>
                                    @endforeach
                                </select>
                                @error('coa_revenue_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Tipe: Pendapatan</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Akun Biaya (HPP)</label>
                                <select class="form-select form-select-sm @error('coa_expense_id') is-invalid @enderror" wire:model="coa_expense_id">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($expenseCoas as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->code }} — {{ $coa->name }}</option>
                                    @endforeach
                                </select>
                                @error('coa_expense_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Tipe: Beban</small>
                            </div>

                            {{-- COA Key-based Mapping --}}
                            <div class="col-12">
                                <hr class="my-1">
                                <h6 class="text-info mb-0"><i class="ri-key-2-line me-1"></i> COA Mapping (Key)</h6>
                                <small class="text-muted">Mapping berdasarkan key akun yang didefinisikan di pengaturan unit usaha. Otomatis terisi dari kategori stok jika tersedia.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Key Persediaan</label>
                                <select class="form-select form-select-sm @error('coa_inventory_key') is-invalid @enderror" wire:model="coa_inventory_key">
                                    <option value="">-- Pilih Key --</option>
                                    @foreach($inventoryKeys as $def)
                                    <option value="{{ $def['key'] }}">{{ $def['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('coa_inventory_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Key Pendapatan</label>
                                <select class="form-select form-select-sm @error('coa_revenue_key') is-invalid @enderror" wire:model="coa_revenue_key">
                                    <option value="">-- Pilih Key --</option>
                                    @foreach($revenueKeys as $def)
                                    <option value="{{ $def['key'] }}">{{ $def['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('coa_revenue_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Key Beban (HPP)</label>
                                <select class="form-select form-select-sm @error('coa_expense_key') is-invalid @enderror" wire:model="coa_expense_key">
                                    <option value="">-- Pilih Key --</option>
                                    @foreach($expenseKeys as $def)
                                    <option value="{{ $def['key'] }}">{{ $def['label'] }}</option>
                                    @endforeach
                                </select>
                                @error('coa_expense_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="groupIsActive">
                                    <label class="form-check-label" for="groupIsActive">
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
