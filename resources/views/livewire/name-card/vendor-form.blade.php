<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Vendor' : 'Tambah Vendor' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        {{-- Info Unit Usaha (hanya untuk konteks attach) --}}
                        @if(!$isEditing)
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Unit Usaha <small class="text-muted">(vendor akan ditautkan ke unit ini)</small></label>
                                @if($isSuperAdmin)
                                <select class="form-select" wire:model="business_unit_id">
                                    <option value="">-- Tanpa Unit (global) --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->first()?->code }} — {{ $units->first()?->name }}" readonly>
                                @endif
                            </div>
                        </div>
                        @endif

                        <h6 class="text-primary mb-3"><i class="ri-information-line"></i> Informasi Dasar</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Kode <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('code') is-invalid @enderror"
                                    wire:model="code" placeholder="Kode vendor (mis: VND-001)">
                                @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Nama <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                    wire:model="name" placeholder="Nama vendor">
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tipe <span class="text-danger">*</span></label>
                                <select class="form-select @error('type') is-invalid @enderror" wire:model="type">
                                    @foreach($types as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Telepon</label>
                                <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                    wire:model="phone" placeholder="Nomor telepon">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                                    wire:model="email" placeholder="Alamat email">
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Kota</label>
                                <input type="text" class="form-control @error('city') is-invalid @enderror"
                                    wire:model="city" placeholder="Kota">
                                @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contact Person</label>
                                <input type="text" class="form-control @error('contact_person') is-invalid @enderror"
                                    wire:model="contact_person" placeholder="Nama contact person">
                                @error('contact_person') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Website</label>
                                <input type="text" class="form-control @error('website') is-invalid @enderror"
                                    wire:model="website" placeholder="https://...">
                                @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Alamat</label>
                                <textarea class="form-control @error('address') is-invalid @enderror"
                                    wire:model="address" rows="2" placeholder="Alamat lengkap"></textarea>
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-primary mb-3"><i class="ri-government-line"></i> Informasi Pajak (PPh23)</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">NPWP</label>
                                <input type="text" class="form-control @error('npwp') is-invalid @enderror"
                                    wire:model="npwp" placeholder="Nomor NPWP">
                                @error('npwp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">NIK</label>
                                <input type="text" class="form-control @error('nik') is-invalid @enderror"
                                    wire:model="nik" placeholder="Nomor Induk Kependudukan">
                                @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">PPh23</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model.live="is_pph23" id="vendorPph23">
                                    <label class="form-check-label" for="vendorPph23">
                                        {{ $is_pph23 ? 'Ya' : 'Tidak' }}
                                    </label>
                                </div>
                            </div>
                            @if($is_pph23)
                            <div class="col-md-2">
                                <label class="form-label">Tarif (%)</label>
                                <input type="number" step="0.01" class="form-control @error('pph23_rate') is-invalid @enderror"
                                    wire:model="pph23_rate" placeholder="2.00">
                                @error('pph23_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @endif
                        </div>

                        <hr class="my-3">
                        <h6 class="text-primary mb-3"><i class="ri-bank-line"></i> Informasi Bank</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Nama Bank</label>
                                <input type="text" class="form-control @error('bank_name') is-invalid @enderror"
                                    wire:model="bank_name" placeholder="Nama bank">
                                @error('bank_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">No. Rekening</label>
                                <input type="text" class="form-control @error('bank_account_number') is-invalid @enderror"
                                    wire:model="bank_account_number" placeholder="Nomor rekening">
                                @error('bank_account_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Atas Nama</label>
                                <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror"
                                    wire:model="bank_account_name" placeholder="Nama pemilik rekening">
                                @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <hr class="my-3">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan tambahan"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" wire:model="is_active" id="vendorIsActive">
                                    <label class="form-check-label" for="vendorIsActive">
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
