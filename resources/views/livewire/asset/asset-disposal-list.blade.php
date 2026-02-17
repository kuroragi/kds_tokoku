<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Aset</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Kode, nama aset...">
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
                    <label class="form-label small text-muted mb-1">Metode</label>
                    <select class="form-select form-select-sm" wire:model.live="filterMethod">
                        <option value="">Semua Metode</option>
                        @foreach($methods as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openAssetDisposalModal')">
                        <i class="ri-add-line"></i> Catat Disposal
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
                        <th width="3%" class="ps-3">#</th>
                        <th width="8%">Kode Aset</th>
                        <th width="14%">Nama Aset</th>
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('disposal_date')">
                            Tanggal @if($sortField === 'disposal_date') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="8%">Metode</th>
                        <th width="12%" class="text-end">Nilai Disposal</th>
                        <th width="12%" class="text-end">Nilai Buku</th>
                        <th width="12%" class="text-end">Laba/Rugi</th>
                        <th width="8%">Jurnal</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($disposals as $idx => $disposal)
                    <tr wire:key="dsp-{{ $disposal->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $disposal->asset->code ?? '-' }}</code></td>
                        <td>{{ $disposal->asset->name ?? '-' }}</td>
                        <td class="text-muted small">{{ $disposal->disposal_date->format('d/m/Y') }}</td>
                        <td>
                            @php
                                $methodColors = ['sold' => 'success', 'scrapped' => 'danger', 'donated' => 'info'];
                            @endphp
                            <span class="badge bg-{{ $methodColors[$disposal->disposal_method] ?? 'secondary' }} bg-opacity-75">
                                {{ $methods[$disposal->disposal_method] ?? $disposal->disposal_method }}
                            </span>
                        </td>
                        <td class="text-end">Rp {{ number_format($disposal->disposal_amount, 0, ',', '.') }}</td>
                        <td class="text-end text-muted">Rp {{ number_format($disposal->book_value_at_disposal, 0, ',', '.') }}</td>
                        <td class="text-end {{ $disposal->gain_loss >= 0 ? 'text-success' : 'text-danger' }} fw-medium">
                            {{ $disposal->gain_loss >= 0 ? '+' : '' }}Rp {{ number_format($disposal->gain_loss, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($disposal->journalMaster)
                            <span class="badge bg-success bg-opacity-75">{{ $disposal->journalMaster->journal_no }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editAssetDisposal', { id: {{ $disposal->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteDisposal({{ $disposal->id }}))"
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
                                <i class="ri-delete-bin-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada catatan disposal</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $disposals->count() }} disposal</small>
        </div>
    </div>
</div>
