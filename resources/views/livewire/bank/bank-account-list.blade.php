<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Rekening</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="No. rekening, nama...">
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
                    <label class="form-label small text-muted mb-1">Bank</label>
                    <select class="form-select form-select-sm" wire:model.live="filterBank">
                        <option value="">Semua Bank</option>
                        @foreach($availableBanks as $bank)
                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
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
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openBankAccountModal')">
                        <i class="ri-add-line"></i> Tambah Rekening
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Cash Accounts --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="ri-wallet-3-line me-1"></i> Kas</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="ps-3">#</th>
                        <th width="25%">Nama Kas</th>
                        <th width="20%">Unit Usaha</th>
                        <th width="15%" class="text-end">Saldo Awal</th>
                        <th width="15%" class="text-end">Saldo Saat Ini</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashAccounts as $idx => $cash)
                    <tr wire:key="cash-{{ $cash->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td class="fw-medium"><i class="ri-wallet-3-line me-1 text-success"></i>{{ $cash->name }}</td>
                        <td><span class="badge bg-info bg-opacity-75">{{ $cash->businessUnit->name }}</span></td>
                        <td class="text-end text-muted">Rp {{ number_format($cash->initial_balance, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $cash->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($cash->current_balance, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-center pe-3">
                            <button class="btn btn-outline-primary btn-sm" wire:click="$dispatch('editCashAccount', { id: {{ $cash->id }} })" title="Edit">
                                <i class="ri-pencil-line"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-3">
                            <span class="text-muted small">Belum ada kas (akan dibuat otomatis saat unit usaha dibuat)</span>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ $cashAccounts->count() }} kas</small>
                <small class="text-success fw-semibold">Total Kas: Rp {{ number_format($cashAccounts->sum('current_balance'), 0, ',', '.') }}</small>
            </div>
        </div>
    </div>

    {{-- Bank Accounts --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="ri-bank-card-line me-1"></i> Rekening Bank</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="4%" class="ps-3">#</th>
                        <th width="12%" style="cursor:pointer" wire:click="sortBy('account_number')">
                            No. Rekening
                            @if($sortField === 'account_number')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="15%" style="cursor:pointer" wire:click="sortBy('account_name')">
                            Nama Pemilik
                            @if($sortField === 'account_name')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%">Bank</th>
                        <th width="10%">Unit Usaha</th>
                        <th width="12%" class="text-end">Saldo Awal</th>
                        <th width="12%" class="text-end" style="cursor:pointer" wire:click="sortBy('current_balance')">
                            Saldo Saat Ini
                            @if($sortField === 'current_balance')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bankAccounts as $idx => $account)
                    <tr wire:key="account-{{ $account->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-muted">{{ $account->account_number }}</code></td>
                        <td class="fw-medium">{{ $account->account_name }}</td>
                        <td><span class="badge bg-primary bg-opacity-75">{{ $account->bank->name }}</span></td>
                        <td><span class="badge bg-info bg-opacity-75">{{ $account->businessUnit->name }}</span></td>
                        <td class="text-end text-muted">Rp {{ number_format($account->initial_balance, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <span class="fw-semibold {{ $account->current_balance >= 0 ? 'text-success' : 'text-danger' }}">
                                Rp {{ number_format($account->current_balance, 0, ',', '.') }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input" type="checkbox" {{ $account->is_active ? 'checked' : '' }}
                                    wire:click="toggleStatus({{ $account->id }})">
                            </div>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="$dispatch('editBankAccount', { id: {{ $account->id }} })" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deleteAccount({{ $account->id }}))" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-bank-card-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada rekening bank</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: {{ $bankAccounts->count() }} rekening</small>
                <small class="text-primary fw-semibold">Total Bank: Rp {{ number_format($bankAccounts->sum('current_balance'), 0, ',', '.') }}</small>
            </div>
        </div>
    </div>
</div>
