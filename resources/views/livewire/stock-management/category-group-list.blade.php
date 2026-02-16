<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Cari Grup</label>
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
                    <label class="form-label small text-muted mb-1">Kategori</label>
                    <select class="form-select form-select-sm" wire:model.live="filterCategory">
                        <option value="">Semua Kategori</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-aktif</option>
                    </select>
                </div>
                <div class="col-lg-4 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openCategoryGroupModal')">
                        <i class="ri-add-line"></i> Tambah Grup
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
                        <th width="8%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="14%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%">Kategori</th>
                        <th width="12%">Unit Usaha</th>
                        <th width="12%">Akun Persediaan</th>
                        <th width="12%">Akun Pendapatan</th>
                        <th width="12%">Akun Biaya</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($groups as $idx => $group)
                    <tr wire:key="group-{{ $group->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $group->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $group->name }}</div>
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-75">{{ $group->stockCategory->name ?? '-' }}</span>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-75">{{ $group->businessUnit->name }}</span>
                        </td>
                        <td class="small">
                            @if($group->coaInventory)
                                <span class="text-muted">{{ $group->coaInventory->code }}</span> - {{ Str::limit($group->coaInventory->name, 20) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($group->coaRevenue)
                                <span class="text-muted">{{ $group->coaRevenue->code }}</span> - {{ Str::limit($group->coaRevenue->name, 20) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="small">
                            @if($group->coaExpense)
                                <span class="text-muted">{{ $group->coaExpense->code }}</span> - {{ Str::limit($group->coaExpense->name, 20) }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $group->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $group->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editCategoryGroup', { id: {{ $group->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteGroup({{ $group->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-folder-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada grup kategori</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $groups->count() }} grup kategori</small>
        </div>
    </div>
</div>
