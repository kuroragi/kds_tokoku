<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Komponen</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Kode, nama...">
                    </div>
                </div>
                @if($isSuperAdmin)
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua Unit</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Tipe</label>
                    <select class="form-select form-select-sm" wire:model.live="filterType">
                        <option value="">Semua Tipe</option>
                        @foreach(\App\Models\SalaryComponent::TYPES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Kategori</label>
                    <select class="form-select form-select-sm" wire:model.live="filterCategory">
                        <option value="">Semua Kategori</option>
                        @foreach(\App\Models\SalaryComponent::CATEGORIES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-outline-secondary btn-sm me-1" wire:click="seedDefaults"
                        wire:confirm="Seed komponen gaji default untuk unit usaha terpilih?">
                        <i class="ri-refresh-line"></i> Seed Default
                    </button>
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSalaryComponentModal')">
                        <i class="ri-add-line"></i> Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="4%" class="ps-3">#</th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="20%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%">Tipe</th>
                        <th width="12%">Kategori</th>
                        <th width="10%">Metode</th>
                        <th width="10%">Perhitungan</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="9%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($components as $idx => $comp)
                    <tr wire:key="comp-{{ $comp->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $comp->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $comp->name }}</div>
                            @if($comp->setting_key)
                            <small class="text-muted">Setting: {{ $comp->setting_key }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeColor = match($comp->type) {
                                    'earning' => 'success',
                                    'deduction' => 'danger',
                                    'benefit' => 'info',
                                    default => 'secondary',
                                };
                            @endphp
                            <span class="badge bg-{{ $typeColor }} bg-opacity-75">
                                {{ \App\Models\SalaryComponent::TYPES[$comp->type] ?? $comp->type }}
                            </span>
                        </td>
                        <td class="small">{{ \App\Models\SalaryComponent::CATEGORIES[$comp->category] ?? $comp->category }}</td>
                        <td class="small">{{ \App\Models\SalaryComponent::APPLY_METHODS[$comp->apply_method] ?? $comp->apply_method }}</td>
                        <td class="small">{{ \App\Models\SalaryComponent::CALCULATION_TYPES[$comp->calculation_type] ?? $comp->calculation_type }}</td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $comp->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $comp->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editSalaryComponent', { id: {{ $comp->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteComponent({{ $comp->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-list-check" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada komponen gaji</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $components->count() }} komponen</small>
        </div>
    </div>
</div>
