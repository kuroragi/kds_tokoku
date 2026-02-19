<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Bank</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Kode, nama, SWIFT...">
                    </div>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        <option value="1">Aktif</option>
                        <option value="0">Non-aktif</option>
                    </select>
                </div>
                <div class="col-lg-7 text-end">
                    <button class="btn btn-outline-info btn-sm me-1" wire:click="$dispatch('openFeeMatrixModal')">
                        <i class="ri-money-dollar-circle-line"></i> Tambah Fee Matrix
                    </button>
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openBankModal')">
                        <i class="ri-add-line"></i> Tambah Bank
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bank Table --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="ri-bank-line me-1"></i> Daftar Bank</h6>
        </div>
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
                            Nama Bank
                            @if($sortField === 'name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="15%">SWIFT Code</th>
                        <th width="12%" class="text-center">Jml Rekening</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($banks as $idx => $bank)
                    <tr wire:key="bank-{{ $bank->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $bank->code }}</code></td>
                        <td class="fw-medium">{{ $bank->name }}</td>
                        <td class="text-muted">{{ $bank->swift_code ?? '-' }}</td>
                        <td class="text-center"><span class="badge bg-light text-dark">{{ $bank->bank_accounts_count }}</span></td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $bank->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $bank->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editBank', { id: {{ $bank->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteBank({{ $bank->id }}))" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-bank-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada data bank</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $banks->count() }} bank</small>
        </div>
    </div>

    {{-- Fee Matrix Table --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="ri-money-dollar-circle-line me-1"></i> Fee Matrix Antar Bank</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="ps-3">#</th>
                        <th width="20%">Bank Asal</th>
                        <th width="20%">Bank Tujuan</th>
                        <th width="12%">Tipe Transfer</th>
                        <th width="12%" class="text-end">Biaya</th>
                        <th width="15%">Catatan</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($feeMatrix as $idx => $fee)
                    <tr wire:key="fee-{{ $fee->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>{{ $fee->sourceBank->name ?? '-' }}</td>
                        <td>{{ $fee->destinationBank->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-secondary bg-opacity-75">{{ strtoupper($fee->transfer_type) }}</span>
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($fee->fee, 0, ',', '.') }}</td>
                        <td class="text-muted small">{{ Str::limit($fee->notes, 30) ?? '-' }}</td>
                        <td class="text-center">
                            @if($fee->is_active)
                            <span class="badge bg-success bg-opacity-75">Aktif</span>
                            @else
                            <span class="badge bg-danger bg-opacity-75">Non-aktif</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editFeeMatrix', { id: {{ $fee->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteFeeMatrix({{ $fee->id }}))" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-4">
                            <div class="text-muted">
                                <p class="mb-0">Belum ada fee matrix</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $feeMatrix->count() }} fee matrix</small>
        </div>
    </div>
</div>
