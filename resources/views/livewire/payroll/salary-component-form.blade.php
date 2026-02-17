<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Komponen Gaji' : 'Tambah Komponen Gaji' }}
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
                            <div class="col-md-3">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="GP, TJ-TRP...">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Urutan</label>
                                <input type="number" class="form-control @error('sort_order') is-invalid @enderror"
                                    wire:model="sort_order" min="0">
                                @error('sort_order') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama komponen gaji">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" wire:model="type">
                                    @foreach(\App\Models\SalaryComponent::TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                <select class="form-select @error('category') is-invalid @enderror" wire:model="category">
                                    @foreach(\App\Models\SalaryComponent::CATEGORIES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Metode Penerapan <span class="text-danger">*</span></label>
                                <select class="form-select @error('apply_method') is-invalid @enderror" wire:model="apply_method">
                                    @foreach(\App\Models\SalaryComponent::APPLY_METHODS as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('apply_method') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe Perhitungan</label>
                                <select class="form-select @error('calculation_type') is-invalid @enderror" wire:model="calculation_type">
                                    @foreach(\App\Models\SalaryComponent::CALCULATION_TYPES as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('calculation_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Setting Key</label>
                                <input type="text" class="form-control @error('setting_key') is-invalid @enderror"
                                    wire:model="setting_key" placeholder="Key payroll_settings">
                                <small class="text-muted">Untuk komponen BPJS yang otomatis</small>
                                @error('setting_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Default Nominal</label>
                                <input type="number" class="form-control @error('default_amount') is-invalid @enderror"
                                    wire:model="default_amount" placeholder="0">
                                @error('default_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" wire:model="is_taxable" id="isTaxable">
                                    <label class="form-check-label" for="isTaxable">Kena Pajak (Taxable)</label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="compIsActive">
                                    <label class="form-check-label" for="compIsActive">
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
