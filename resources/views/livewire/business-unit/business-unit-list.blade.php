<div class="card-body p-0">
    <!-- Search & Filter -->
    <div class="bg-light p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text"><i class="ri-search-line"></i></span>
                    <input type="text" class="form-control" placeholder="Cari kode, nama, atau pemilik..." wire:model.live.debounce.300ms="search">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" wire:model.live="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Non-aktif</option>
                </select>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ route('business-unit.create') }}" class="btn btn-primary">
                    <i class="ri-add-line"></i> Tambah Unit Usaha
                </a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th width="5%">#</th>
                    <th width="10%">
                        <button type="button" class="btn btn-link text-white p-0 text-decoration-none" wire:click="sortBy('code')">
                            Kode @if($sortField === 'code') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i> @endif
                        </button>
                    </th>
                    <th width="25%">
                        <button type="button" class="btn btn-link text-white p-0 text-decoration-none" wire:click="sortBy('name')">
                            Nama Unit @if($sortField === 'name') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-line"></i> @endif
                        </button>
                    </th>
                    <th width="15%">Pemilik</th>
                    <th width="10%" class="text-center">Jenis</th>
                    <th width="10%" class="text-center">Users</th>
                    <th width="10%" class="text-center">Status</th>
                    <th width="15%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($units as $i => $unit)
                <tr wire:key="unit-{{ $unit->id }}">
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td><span class="badge bg-primary-subtle text-primary fw-semibold">{{ $unit->code }}</span></td>
                    <td>
                        <div>
                            <a href="{{ route('business-unit.edit', $unit) }}" class="fw-semibold text-dark text-decoration-none">
                                {{ $unit->name }}
                            </a>
                            @if($unit->city)
                                <br><small class="text-muted"><i class="ri-map-pin-line"></i> {{ $unit->city }}</small>
                            @endif
                        </div>
                    </td>
                    <td>{{ $unit->owner_name ?? '-' }}</td>
                    <td class="text-center">
                        @if($unit->business_type)
                            <span class="badge bg-info-subtle text-info">{{ ucfirst($unit->business_type) }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-secondary">{{ $unit->users_count }}</span>
                    </td>
                    <td class="text-center">
                        <div class="form-check form-switch d-inline-block">
                            <input class="form-check-input" type="checkbox" {{ $unit->is_active ? 'checked' : '' }}
                                wire:click="toggleStatus({{ $unit->id }})">
                        </div>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('business-unit.edit', $unit) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                            <i class="ri-pencil-line"></i>
                        </a>
                        @if($unit->users_count === 0)
                        <button class="btn btn-sm btn-outline-danger" title="Hapus"
                            onclick="if(confirm('Hapus unit usaha {{ $unit->name }}?')) @this.call('deleteUnit', {{ $unit->id }})">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <i class="ri-store-2-line" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">Belum ada unit usaha terdaftar.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($units->count() > 0)
    <div class="px-3 py-2 border-top bg-light">
        <small class="text-muted">Total: <strong>{{ $units->count() }}</strong> unit usaha</small>
    </div>
    @endif
</div>
