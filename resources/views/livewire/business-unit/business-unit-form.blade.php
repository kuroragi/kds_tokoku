<div>
    <form wire:submit="save">
        {{-- Card with Tabs --}}
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-bottom p-0">
                <div class="d-flex justify-content-between align-items-center px-3 pt-3 pb-0">
                    <div>
                        <h5 class="mb-0">
                            <i class="ri-store-2-line text-primary me-2"></i>
                            {{ $isEditing ? 'Edit Unit Usaha' : 'Tambah Unit Usaha Baru' }}
                        </h5>
                        <p class="text-muted mb-0 small">
                            {{ $isEditing ? "Kode: {$code}" : 'Isi profile dan konfigurasi akun COA' }}
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('business-unit.index') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="ri-arrow-left-line"></i> Kembali
                        </a>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </div>

                {{-- Tab Navigation --}}
                <ul class="nav nav-tabs mt-3 px-3" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $activeTab === 'profile' ? 'active' : '' }}"
                            wire:click="setActiveTab('profile')">
                            <i class="ri-user-line me-1"></i> Profil Unit
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $activeTab === 'coa_aktiva' ? 'active' : '' }}"
                            wire:click="setActiveTab('coa_aktiva')">
                            <i class="ri-safe-2-line me-1"></i> Akun Aktiva
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $activeTab === 'coa_pasiva' ? 'active' : '' }}"
                            wire:click="setActiveTab('coa_pasiva')">
                            <i class="ri-hand-coin-line me-1"></i> Akun Pasiva
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $activeTab === 'coa_modal' ? 'active' : '' }}"
                            wire:click="setActiveTab('coa_modal')">
                            <i class="ri-funds-line me-1"></i> Akun Modal
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $activeTab === 'coa_pendapatan' ? 'active' : '' }}"
                            wire:click="setActiveTab('coa_pendapatan')">
                            <i class="ri-money-dollar-circle-line me-1"></i> Akun Pendapatan
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button type="button" class="nav-link {{ $activeTab === 'coa_beban' ? 'active' : '' }}"
                            wire:click="setActiveTab('coa_beban')">
                            <i class="ri-money-dollar-box-line me-1"></i> Akun Beban
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body">
                {{-- ===== TAB: PROFIL ===== --}}
                @if($activeTab === 'profile')
                <div class="row g-3">
                    {{-- Left Column: Identity --}}
                    <div class="col-lg-6">
                        <div class="card border h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="ri-building-line me-1"></i> Identitas Unit</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Kode Unit <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('code') is-invalid @enderror" wire:model="code" placeholder="UNT-001">
                                        @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Nama Unit Usaha <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" wire:model="name" placeholder="Toko Sejahtera">
                                        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Pemilik</label>
                                        <input type="text" class="form-control" wire:model="owner_name" placeholder="Ahmad Susanto">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Usaha</label>
                                        <select class="form-select" wire:model="business_type">
                                            <option value="">-- Pilih Jenis --</option>
                                            <option value="toko">Toko / Retail</option>
                                            <option value="jasa">Jasa</option>
                                            <option value="manufaktur">Manufaktur</option>
                                            <option value="distributor">Distributor</option>
                                            <option value="food_beverage">Food & Beverage</option>
                                            <option value="lainnya">Lainnya</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">NPWP</label>
                                        <input type="text" class="form-control" wire:model="tax_id" placeholder="XX.XXX.XXX.X-XXX.XXX">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status</label>
                                        <div class="form-check form-switch mt-2">
                                            <input class="form-check-input" type="checkbox" wire:model="is_active" id="unitIsActive">
                                            <label class="form-check-label" for="unitIsActive">
                                                {{ $is_active ? 'Aktif' : 'Non-aktif' }}
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Deskripsi</label>
                                        <textarea class="form-control" wire:model="description" rows="3" placeholder="Keterangan singkat tentang unit usaha"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Right Column: Contact & Address --}}
                    <div class="col-lg-6">
                        <div class="card border h-100">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0"><i class="ri-contacts-line me-1"></i> Kontak & Alamat</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Telepon</label>
                                        <input type="text" class="form-control" wire:model="phone" placeholder="08xx-xxxx-xxxx">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Email</label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" wire:model="email" placeholder="unit@tokoku.com">
                                        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Alamat</label>
                                        <textarea class="form-control" wire:model="address" rows="3" placeholder="Jl. Contoh No. 123"></textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kota</label>
                                        <input type="text" class="form-control" wire:model="city" placeholder="Jakarta">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Provinsi</label>
                                        <input type="text" class="form-control" wire:model="province" placeholder="DKI Jakarta">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Kode Pos</label>
                                        <input type="text" class="form-control" wire:model="postal_code" placeholder="12345">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ===== TAB: COA MAPPING per TYPE ===== --}}
                @foreach(['aktiva', 'pasiva', 'modal', 'pendapatan', 'beban'] as $coaType)
                    @if($activeTab === 'coa_' . $coaType)
                    @php
                        $typeLabels = [
                            'aktiva' => ['title' => 'Akun Aktiva (Harta)', 'icon' => 'ri-safe-2-line', 'color' => 'primary'],
                            'pasiva' => ['title' => 'Akun Pasiva (Kewajiban)', 'icon' => 'ri-hand-coin-line', 'color' => 'warning'],
                            'modal' => ['title' => 'Akun Modal (Ekuitas)', 'icon' => 'ri-funds-line', 'color' => 'info'],
                            'pendapatan' => ['title' => 'Akun Pendapatan', 'icon' => 'ri-money-dollar-circle-line', 'color' => 'success'],
                            'beban' => ['title' => 'Akun Beban', 'icon' => 'ri-money-dollar-box-line', 'color' => 'danger'],
                        ];
                        $meta = $typeLabels[$coaType];
                        $defs = $accountKeyDefinitions[$coaType] ?? [];
                        $coaOptions = $coasByType[$coaType] ?? collect();
                    @endphp

                    <div class="card border">
                        <div class="card-header bg-{{ $meta['color'] }} bg-opacity-10 py-2">
                            <h6 class="mb-0 text-{{ $meta['color'] }}">
                                <i class="{{ $meta['icon'] }} me-1"></i> {{ $meta['title'] }}
                            </h6>
                            <small class="text-muted">Pilih akun COA yang digunakan unit usaha ini untuk setiap keperluan transaksi</small>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Fungsi Akun</th>
                                        <th width="50%">Akun COA</th>
                                        <th width="15%" class="text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($defs as $idx => $def)
                                    <tr wire:key="coa-{{ $coaType }}-{{ $def['key'] }}">
                                        <td class="text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="fw-medium">{{ $def['label'] }}</div>
                                            <small class="text-muted font-monospace">{{ $def['key'] }}</small>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm"
                                                wire:model.live="coaMappings.{{ $def['key'] }}">
                                                <option value="">-- Belum Dipilih --</option>
                                                @foreach($coaOptions as $coa)
                                                <option value="{{ $coa->id }}">{{ $coa->code }} — {{ $coa->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="text-center">
                                            @if(!empty($coaMappings[$def['key']]))
                                                <span class="badge bg-success"><i class="ri-checkbox-circle-line"></i> Set</span>
                                            @else
                                                <span class="badge bg-secondary"><i class="ri-close-circle-line"></i> Kosong</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-light py-2">
                            @php
                                $filled = collect($defs)->filter(fn($d) => !empty($coaMappings[$d['key']]))->count();
                            @endphp
                            <small class="text-muted">
                                <i class="ri-information-line"></i>
                                Terisi: <strong>{{ $filled }}</strong> / {{ count($defs) }} akun
                            </small>
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('business-unit.index') }}" class="btn btn-outline-secondary">
                    <i class="ri-arrow-left-line"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                    <span wire:loading.remove><i class="ri-save-line"></i> Simpan</span>
                    <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                </button>
            </div>
        </div>
    </form>
</div>
