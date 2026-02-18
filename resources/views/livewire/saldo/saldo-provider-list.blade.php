<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Penyedia</label>
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
                        @foreach($types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
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
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSaldoProviderModal')">
                        <i class="ri-add-line"></i> Tambah Penyedia
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
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('code')">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="18%" style="cursor:pointer" wire:click="sortBy('name')">
                            Nama
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%">Tipe</th>
                        <th width="15%" class="text-end" style="cursor:pointer" wire:click="sortBy('current_balance')">
                            Saldo Saat Ini
                            @if($sortField === 'current_balance')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%">Unit Usaha</th>
                        <th width="8%" class="text-center">Top Up</th>
                        <th width="8%" class="text-center">Trx</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($providers as $idx => $provider)
                    <tr wire:key="provider-{{ $provider->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $provider->code }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $provider->name }}</div>
                            @if($provider->description)
                            <small class="text-muted">{{ Str::limit($provider->description, 40) }}</small>
                            @endif
                        </td>
                        <td>
                            @php
                                $typeBadge = match($provider->type) {
                                    'e-wallet' => 'bg-primary',
                                    'bank' => 'bg-success',
                                    'other' => 'bg-secondary',
                                    default => 'bg-secondary',
                                };
                            @endphp
                            <span class="badge {{ $typeBadge }} bg-opacity-75">{{ $types[$provider->type] ?? $provider->type }}</span>
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $provider->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($provider->current_balance, 0, ',', '.') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-75">{{ $provider->businessUnit->name }}</span>
                        </td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $provider->topups_count }}</span></td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $provider->transactions_count }}</span></td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $provider->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $provider->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editSaldoProvider', { id: {{ $provider->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteProvider({{ $provider->id }}))"
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
                                <i class="bi bi-credit-card-2-front" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada penyedia saldo</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ $providers->count() }} penyedia</small>
                <small class="text-muted fw-semibold">Total Saldo: Rp {{ number_format($providers->sum('current_balance'), 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
</div>
