<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="No faktur, deskripsi, vendor...">
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
                    <label class="form-label small text-muted mb-1">Vendor</label>
                    <select class="form-select form-select-sm" wire:model.live="filterVendor">
                        <option value="">Semua Vendor</option>
                        @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openPayableModal')">
                        <i class="ri-add-line"></i> Tambah Hutang
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
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('invoice_number')">
                            No. Faktur @if($sortField === 'invoice_number') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="12%">Vendor</th>
                        @if($isSuperAdmin)
                        <th width="8%">Unit</th>
                        @endif
                        <th width="9%" style="cursor:pointer" wire:click="sortBy('invoice_date')">
                            Tgl Faktur @if($sortField === 'invoice_date') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="9%" style="cursor:pointer" wire:click="sortBy('due_date')">
                            Jatuh Tempo @if($sortField === 'due_date') <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i> @endif
                        </th>
                        <th width="10%" class="text-end">DPP</th>
                        <th width="8%" class="text-end">PPh23</th>
                        <th width="10%" class="text-end">Harus Dibayar</th>
                        <th width="10%" class="text-end">Sisa</th>
                        <th width="7%" class="text-center">Status</th>
                        <th width="10%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payables as $idx => $payable)
                    <tr wire:key="payable-{{ $payable->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td><code class="text-primary">{{ $payable->invoice_number }}</code></td>
                        <td>
                            <div class="fw-medium">{{ $payable->vendor->name }}</div>
                            @if($payable->description)
                            <small class="text-muted">{{ Str::limit($payable->description, 30) }}</small>
                            @endif
                        </td>
                        @if($isSuperAdmin)
                        <td class="text-muted small">{{ $payable->businessUnit->name ?? '-' }}</td>
                        @endif
                        <td class="text-muted small">{{ $payable->invoice_date->format('d/m/Y') }}</td>
                        <td class="small">
                            <span class="{{ $payable->is_overdue ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $payable->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-end small">Rp {{ number_format($payable->dpp, 0, ',', '.') }}</td>
                        <td class="text-end small">
                            @if($payable->pph23_amount > 0)
                            <span class="text-info">Rp {{ number_format($payable->pph23_amount, 0, ',', '.') }}</span>
                            @else
                            <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">Rp {{ number_format($payable->amount_due, 0, ',', '.') }}</td>
                        <td class="text-end fw-medium">
                            @if($payable->remaining > 0)
                            Rp {{ number_format($payable->remaining, 0, ',', '.') }}
                            @else
                            <span class="text-success">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $statusColors = ['unpaid' => 'warning', 'partial' => 'info', 'paid' => 'success', 'void' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$payable->status] ?? 'secondary' }}">
                                {{ $statuses[$payable->status] ?? $payable->status }}
                            </span>
                            @if($payable->is_overdue)
                            <br><span class="badge bg-danger mt-1" style="font-size: 0.65em;">Overdue</span>
                            @endif
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                @if(in_array($payable->status, ['unpaid', 'partial']))
                                <button class="btn btn-outline-success"
                                    wire:click="$dispatch('openPayablePaymentModal', { payableId: {{ $payable->id }} })"
                                    title="Bayar">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </button>
                                @endif
                                @if($payable->status === 'unpaid')
                                <button class="btn btn-outline-primary"
                                    wire:click="$dispatch('editPayable', { id: {{ $payable->id }} })"
                                    title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                <button class="btn btn-outline-warning"
                                    onclick="confirmDelete(() => @this.voidPayable({{ $payable->id }}))"
                                    title="Batalkan">
                                    <i class="ri-close-circle-line"></i>
                                </button>
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deletePayable({{ $payable->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isSuperAdmin ? 12 : 11 }}" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-file-list-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada data hutang</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <div class="d-flex justify-content-between">
                <small class="text-muted">Total: {{ $payables->count() }} hutang</small>
                <small class="text-muted">
                    Total Sisa: <strong>Rp {{ number_format($payables->sum(fn($p) => $p->remaining), 0, ',', '.') }}</strong>
                </small>
            </div>
        </div>
    </div>
</div>
