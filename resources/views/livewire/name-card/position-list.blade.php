<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Jabatan</label>
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
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-aktif</option>
                    </select>
                </div>
                <div class="col-lg-5 text-end">
                    @if($isSuperAdmin && $filterUnit)
                    <button class="btn btn-outline-info btn-sm me-1" wire:click="duplicateDefaults({{ $filterUnit }})"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="duplicateDefaults"><i class="ri-file-copy-line"></i> Duplikat Jabatan Default</span>
                        <span wire:loading wire:target="duplicateDefaults"><i class="ri-loader-4-line"></i> Menduplikat...</span>
                    </button>
                    @elseif(!$isSuperAdmin && $units->first())
                    <button class="btn btn-outline-info btn-sm me-1" wire:click="duplicateDefaults({{ $units->first()->id }})"
                        wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="duplicateDefaults"><i class="ri-file-copy-line"></i> Duplikat Jabatan Default</span>
                        <span wire:loading wire:target="duplicateDefaults"><i class="ri-loader-4-line"></i> Menduplikat...</span>
                    </button>
                    @endif
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openPositionModal')">
                        <i class="ri-add-line"></i> Tambah Jabatan
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
                        <th width="5%" class="ps-3">#</th>
                        <th width="12%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="25%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="18%">Unit Usaha</th>
                        <th width="15%">Deskripsi</th>
                        <th width="8%" class="text-center">Karyawan</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="9%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($positions as $idx => $position)
                    <tr wire:key="position-{{ $position->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $position->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $position->name }}</div>
                            @if($position->is_system_default)
                            <small class="text-info"><i class="ri-shield-check-line"></i> Dari Default Sistem</small>
                            @endif
                        </td>
                        <td>
                            @if($position->businessUnit)
                            <span class="badge bg-info bg-opacity-75">{{ $position->businessUnit->name }}</span>
                            @else
                            <span class="badge bg-warning bg-opacity-75">Sistem</span>
                            @endif
                        </td>
                        <td class="text-muted small">{{ Str::limit($position->description, 30) ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge bg-secondary">{{ $position->employees_count }}</span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $position->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $position->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-success" wire:click="$dispatch('openPositionSalaryTemplate', { id: {{ $position->id }} })" title="Template Gaji">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </button>
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editPosition', { id: {{ $position->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deletePosition({{ $position->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-briefcase-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada jabatan</p>
                                <p class="small mb-0">Klik "Duplikat Jabatan Default" untuk menambahkan jabatan bawaan sistem</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $positions->count() }} jabatan</small>
        </div>
    </div>
</div>
