<div>
    <div class="card-body">
        {{-- Filters --}}
        <div class="row g-2 mb-3">
            <div class="col-md-3">
                <input type="text" class="form-control form-control-sm" placeholder="Cari kode/nama..."
                    wire:model.live.debounce.300ms="search">
            </div>
            @if($isSuperAdmin)
            <div class="col-md-2">
                <select class="form-select form-select-sm" wire:model.live="filterUnit">
                    <option value="">Semua Unit</option>
                    @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->code }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-2">
                <select class="form-select form-select-sm" wire:model.live="filterYear">
                    <option value="">Semua Tahun</option>
                    @foreach($availableYears as $yr)
                    <option value="{{ $yr }}">{{ $yr }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select form-select-sm" wire:model.live="filterStatus">
                    <option value="">Semua Status</option>
                    <option value="active">Aktif & Terbuka</option>
                    <option value="closed">Ditutup</option>
                    <option value="inactive">Non-aktif</option>
                </select>
            </div>
        </div>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th wire:click="sortBy('code')" style="cursor:pointer" class="text-nowrap">
                            Kode
                            @if($sortField === 'code')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th wire:click="sortBy('name')" style="cursor:pointer">
                            Nama Periode
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        @if($isSuperAdmin)
                        <th>Unit Usaha</th>
                        @endif
                        <th wire:click="sortBy('start_date')" style="cursor:pointer" class="text-nowrap">
                            Tanggal
                            @if($sortField === 'start_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Jurnal</th>
                        @if($isSuperAdmin)
                        <th class="text-end">Aksi</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $period)
                    <tr>
                        <td><code>{{ $period->code }}</code></td>
                        <td>{{ $period->name }}</td>
                        @if($isSuperAdmin)
                        <td>
                            <small class="text-muted">{{ $period->businessUnit?->code ?? '-' }}</small>
                        </td>
                        @endif
                        <td class="text-nowrap">
                            <small>{{ $period->start_date->format('d/m/Y') }} — {{ $period->end_date->format('d/m/Y') }}</small>
                        </td>
                        <td class="text-center">
                            @if($period->is_closed)
                                <span class="badge bg-danger-subtle text-danger">Ditutup</span>
                            @elseif($period->is_active)
                                @if($period->is_current)
                                    <span class="badge bg-success-subtle text-success">Aktif (Berjalan)</span>
                                @else
                                    <span class="badge bg-primary-subtle text-primary">Aktif</span>
                                @endif
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">Non-aktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info-subtle text-info">{{ $period->total_journals }}</span>
                        </td>
                        @if($isSuperAdmin)
                        <td class="text-end text-nowrap">
                            @if(!$period->is_closed)
                            <button class="btn btn-sm btn-outline-primary" wire:click="$dispatch('editPeriod', { id: {{ $period->id }} })" title="Edit">
                                <i class="ri-pencil-line"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-{{ $period->is_active ? 'warning' : 'success' }}"
                                wire:click="toggleStatus({{ $period->id }})"
                                wire:confirm="Yakin ingin {{ $period->is_active ? 'menonaktifkan' : 'mengaktifkan' }} periode ini?"
                                title="{{ $period->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                <i class="ri-{{ $period->is_active ? 'toggle-line' : 'toggle-fill' }}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger"
                                wire:click="deletePeriod({{ $period->id }})"
                                wire:confirm="Yakin ingin menghapus periode '{{ $period->name }}'?"
                                title="Hapus">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                            @else
                            <span class="text-muted small"><i class="ri-lock-line"></i></span>
                            @endif
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 7 : 5 }}" class="text-center text-muted py-4">
                            <i class="ri-calendar-line ri-2x d-block mb-2"></i>
                            Belum ada periode. {{ $isSuperAdmin ? 'Klik "Tambah Periode" untuk memulai.' : '' }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
