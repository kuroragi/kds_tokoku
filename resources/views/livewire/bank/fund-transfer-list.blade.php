<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Transfer</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="No. referensi, catatan...">
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
                    <label class="form-label small text-muted mb-1">Sumber</label>
                    <select class="form-select form-select-sm" wire:model.live="filterSourceType">
                        <option value="">Semua</option>
                        <option value="cash">Kas</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Tujuan</label>
                    <select class="form-select form-select-sm" wire:model.live="filterDestType">
                        <option value="">Semua</option>
                        <option value="cash">Kas</option>
                        <option value="bank">Bank</option>
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openFundTransferModal')">
                        <i class="ri-add-line"></i> Tambah Transfer
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
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('transfer_date')">
                            Tanggal
                            @if($sortField === 'transfer_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="15%">Sumber</th>
                        <th width="15%">Tujuan</th>
                        <th width="12%" class="text-end" style="cursor:pointer" wire:click="sortBy('amount')">
                            Jumlah
                            @if($sortField === 'amount')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="10%" class="text-end">Biaya Admin</th>
                        <th width="10%" class="text-end">Total Dipotong</th>
                        <th width="10%">Referensi</th>
                        <th width="6%">Catatan</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $idx => $transfer)
                    <tr wire:key="transfer-{{ $transfer->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>{{ $transfer->transfer_date->format('d/m/Y') }}</td>
                        <td>
                            @if($transfer->source_type === 'cash')
                            <span class="badge bg-success bg-opacity-75"><i class="ri-wallet-3-line me-1"></i>Kas</span>
                            @else
                            <span class="badge bg-primary bg-opacity-75">{{ $transfer->sourceBankAccount->bank->name ?? '-' }}</span>
                            <div class="small text-muted">{{ $transfer->sourceBankAccount->account_number ?? '' }}</div>
                            @endif
                        </td>
                        <td>
                            @if($transfer->destination_type === 'cash')
                            <span class="badge bg-success bg-opacity-75"><i class="ri-wallet-3-line me-1"></i>Kas</span>
                            @else
                            <span class="badge bg-primary bg-opacity-75">{{ $transfer->destinationBankAccount->bank->name ?? '-' }}</span>
                            <div class="small text-muted">{{ $transfer->destinationBankAccount->account_number ?? '' }}</div>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($transfer->amount, 0, ',', '.') }}</td>
                        <td class="text-end text-muted">
                            @if($transfer->admin_fee > 0)
                            Rp {{ number_format($transfer->admin_fee, 0, ',', '.') }}
                            @else
                            -
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold text-danger">Rp {{ number_format($transfer->total_deducted, 0, ',', '.') }}</span>
                        </td>
                        <td class="text-muted small">{{ $transfer->reference_no ?? '-' }}</td>
                        <td class="text-muted small">{{ Str::limit($transfer->notes, 15) ?? '-' }}</td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editFundTransfer', { id: {{ $transfer->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteTransfer({{ $transfer->id }}))" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-exchange-funds-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada data transfer</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ $transfers->count() }} transfer</small>
                <div>
                    <small class="text-muted me-3">Total Transfer: <span class="fw-semibold">Rp {{ number_format($transfers->sum('amount'), 0, ',', '.') }}</span></small>
                    <small class="text-danger fw-semibold">Total Admin Fee: Rp {{ number_format($transfers->sum('admin_fee'), 0, ',', '.') }}</small>
                </div>
            </div>
        </div>
    </div>
</div>
