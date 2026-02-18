<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Top Up</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Referensi, catatan...">
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
                    <label class="form-label small text-muted mb-1">Penyedia</label>
                    <select class="form-select form-select-sm" wire:model.live="filterProvider">
                        <option value="">Semua Penyedia</option>
                        @foreach($availableProviders as $provider)
                        <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Metode</label>
                    <select class="form-select form-select-sm" wire:model.live="filterMethod">
                        <option value="">Semua Metode</option>
                        @foreach($methods as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openSaldoTopupModal')">
                        <i class="ri-add-line"></i> Tambah Top Up
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
                        <th width="12%" style="cursor:pointer" wire:click="sortBy('topup_date')">
                            Tanggal
                            @if($sortField === 'topup_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="15%">Penyedia</th>
                        <th width="14%" class="text-end" style="cursor:pointer" wire:click="sortBy('amount')">
                            Jumlah
                            @if($sortField === 'amount')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%" class="text-end">Biaya</th>
                        <th width="10%">Metode</th>
                        <th width="12%">Referensi</th>
                        <th width="14%">Catatan</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topups as $idx => $topup)
                    <tr wire:key="topup-{{ $topup->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>{{ $topup->topup_date->format('d/m/Y') }}</td>
                        <td>
                            <span class="badge bg-info bg-opacity-75">{{ $topup->saldoProvider->name }}</span>
                        </td>
                        <td class="text-end fw-semibold text-success">
                            Rp {{ number_format($topup->amount, 0, ',', '.') }}
                        </td>
                        <td class="text-end text-muted">
                            @if($topup->fee > 0)
                            Rp {{ number_format($topup->fee, 0, ',', '.') }}
                            @else
                            -
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-75">{{ $methods[$topup->method] ?? $topup->method }}</span>
                        </td>
                        <td class="text-muted small">{{ $topup->reference_no ?? '-' }}</td>
                        <td class="text-muted small">{{ Str::limit($topup->notes, 30) ?? '-' }}</td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editSaldoTopup', { id: {{ $topup->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteTopup({{ $topup->id }}))"
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
                                <i class="ri-wallet-3-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada data top up</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ $topups->count() }} top up</small>
                <small class="text-muted fw-semibold">Total: Rp {{ number_format($topups->sum('amount'), 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
</div>
