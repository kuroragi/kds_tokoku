<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Karyawan' : 'Tambah Karyawan' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Tabs --}}
                        <ul class="nav nav-tabs nav-tabs-custom mb-3">
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'umum' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="$set('activeTab', 'umum')">
                                    <i class="ri-user-line me-1"></i> Data Umum
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link {{ $activeTab === 'gaji' ? 'active' : '' }}" href="#"
                                    wire:click.prevent="$set('activeTab', 'gaji')">
                                    <i class="ri-money-dollar-circle-line me-1"></i> Data Gaji
                                </a>
                            </li>
                        </ul>

                        {{-- Tab: Data Umum --}}
                        <div class="{{ $activeTab === 'umum' ? '' : 'd-none' }}">
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
                                    <label class="form-label">Jabatan</label>
                                    <select class="form-select @error('position_id') is-invalid @enderror" wire:model="position_id">
                                        <option value="">-- Pilih Jabatan --</option>
                                        @foreach($positions as $pos)
                                        <option value="{{ $pos->id }}">{{ $pos->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('position_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kode <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror"
                                        wire:model="code" placeholder="Kode karyawan (mis: EMP-001)">
                                    @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        wire:model="name" placeholder="Nama lengkap karyawan">
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NIK</label>
                                    <input type="text" class="form-control @error('nik') is-invalid @enderror"
                                        wire:model="nik" placeholder="Nomor Induk Kependudukan">
                                    @error('nik') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Telepon</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        wire:model="phone" placeholder="Nomor telepon">
                                    @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control @error('email') is-invalid @enderror"
                                        wire:model="email" placeholder="Alamat email">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Masuk</label>
                                    <input type="date" class="form-control @error('join_date') is-invalid @enderror"
                                        wire:model="join_date">
                                    @error('join_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label">Alamat</label>
                                    <textarea class="form-control @error('address') is-invalid @enderror"
                                        wire:model="address" rows="2" placeholder="Alamat lengkap"></textarea>
                                    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" wire:model="is_active" id="empIsActive">
                                        <label class="form-check-label" for="empIsActive">
                                            {{ $is_active ? 'Aktif' : 'Non-aktif' }}
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab: Data Gaji --}}
                        <div class="{{ $activeTab === 'gaji' ? '' : 'd-none' }}">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Link ke User Sistem</label>
                                    <select class="form-select @error('user_id') is-invalid @enderror" wire:model="user_id">
                                        <option value="">-- Tidak Terhubung --</option>
                                        @foreach($this->users as $usr)
                                        <option value="{{ $usr->id }}">{{ $usr->name }} ({{ $usr->username }})</option>
                                        @endforeach
                                    </select>
                                    @error('user_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gaji Pokok</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control @error('base_salary') is-invalid @enderror"
                                            wire:model="base_salary" placeholder="0" min="0">
                                    </div>
                                    @error('base_salary') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <hr class="my-1">
                                    <small class="text-muted fw-semibold">DATA BANK</small>
                                </div>
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
                                    <label class="form-label">Nama Pemilik Rekening</label>
                                    <input type="text" class="form-control @error('bank_account_name') is-invalid @enderror"
                                        wire:model="bank_account_name" placeholder="Atas nama">
                                    @error('bank_account_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12">
                                    <hr class="my-1">
                                    <small class="text-muted fw-semibold">DATA PAJAK</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">NPWP</label>
                                    <input type="text" class="form-control @error('npwp') is-invalid @enderror"
                                        wire:model="npwp" placeholder="Nomor NPWP">
                                    @error('npwp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Status PTKP</label>
                                    <select class="form-select @error('ptkp_status') is-invalid @enderror" wire:model="ptkp_status">
                                        <option value="">-- Pilih --</option>
                                        @foreach(\App\Models\Employee::PTKP_STATUSES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('ptkp_status') <div class="invalid-feedback">{{ $message }}</div> @enderror
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
