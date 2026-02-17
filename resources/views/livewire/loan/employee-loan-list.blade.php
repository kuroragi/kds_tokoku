<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Pinjaman</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="No. pinjaman / nama karyawan...">
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
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua Status</option>
                        @foreach($statuses as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 ms-auto text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openEmployeeLoanForm')">
                        <i class="ri-add-line"></i> Buat Pinjaman
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
                        <th width="14%">No. Pinjaman</th>
                        <th width="16%">Karyawan</th>
                        @if($isSuperAdmin)
                        <th width="10%">Unit</th>
                        @endif
                        <th width="12%" class="text-end">Jumlah</th>
                        <th width="8%" class="text-center">Cicilan</th>
                        <th width="12%" class="text-end">Sisa</th>
                        <th width="8%" class="text-center">Progress</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loans as $i => $loan)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('employee-loan.detail', $loan) }}"
                                class="fw-semibold text-primary text-decoration-none">
                                {{ $loan->loan_number }}
                            </a>
                            <div class="text-muted small">{{ $loan->disbursed_date->format('d/m/Y') }}</div>
                        </td>
                        <td>
                            <div class="fw-medium">{{ $loan->employee->name ?? '-' }}</div>
                            <div class="text-muted small">{{ $loan->employee->code ?? '' }}</div>
                        </td>
                        @if($isSuperAdmin)
                        <td class="small">{{ $loan->businessUnit->name ?? '-' }}</td>
                        @endif
                        <td class="text-end">Rp {{ number_format($loan->loan_amount) }}</td>
                        <td class="text-center small">{{ $loan->installment_count }}x</td>
                        <td class="text-end">
                            @if($loan->remaining_amount > 0)
                            <span class="text-danger">Rp {{ number_format($loan->remaining_amount) }}</span>
                            @else
                            <span class="text-success">Rp 0</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: {{ $loan->progress_percent }}%"></div>
                            </div>
                            <small class="text-muted">{{ $loan->progress_percent }}%</small>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ \App\Models\EmployeeLoan::STATUS_COLORS[$loan->status] ?? 'secondary' }}">
                                {{ \App\Models\EmployeeLoan::STATUSES[$loan->status] ?? $loan->status }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('employee-loan.detail', $loan) }}"
                                class="btn btn-soft-info btn-sm" title="Detail">
                                <i class="ri-eye-line"></i>
                            </a>
                            @if($loan->status === 'active' && $loan->total_paid == 0)
                            <button class="btn btn-soft-danger btn-sm"
                                wire:click="voidLoan({{ $loan->id }})"
                                wire:confirm="Batalkan pinjaman {{ $loan->loan_number }}?"
                                title="Batalkan">
                                <i class="ri-close-circle-line"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ri-inbox-line fs-3 d-block mb-2"></i>
                            Belum ada data pinjaman.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
