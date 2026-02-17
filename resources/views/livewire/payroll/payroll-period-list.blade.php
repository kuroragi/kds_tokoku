<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Payroll</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                            placeholder="Nama payroll...">
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
                        @foreach(\App\Models\PayrollPeriod::STATUSES as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Tahun</label>
                    <select class="form-select form-select-sm" wire:model.live="filterYear">
                        <option value="">Semua Tahun</option>
                        @foreach($years as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="$dispatch('openPayrollPeriodModal')">
                        <i class="ri-add-line"></i> Buat Payroll
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
                        <th width="20%">Nama</th>
                        <th width="12%">Unit Usaha</th>
                        <th width="10%">Periode</th>
                        <th width="12%" class="text-end">Total Gaji</th>
                        <th width="12%" class="text-end">Total Bersih</th>
                        <th width="10%" class="text-center">Status</th>
                        <th width="12%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $idx => $period)
                    <tr wire:key="payroll-{{ $period->id }}">
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-medium">{{ $period->name }}</div>
                            @if($period->paid_date)
                            <small class="text-muted">Dibayar: {{ $period->paid_date->format('d/m/Y') }}</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-info bg-opacity-75">{{ $period->businessUnit->name }}</span>
                        </td>
                        <td class="small text-muted">{{ str_pad($period->month, 2, '0', STR_PAD_LEFT) }}/{{ $period->year }}</td>
                        <td class="text-end small">Rp {{ number_format($period->total_earnings + $period->total_benefits) }}</td>
                        <td class="text-end small fw-medium">Rp {{ number_format($period->total_net) }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ \App\Models\PayrollPeriod::STATUS_COLORS[$period->status] ?? 'secondary' }}">
                                {{ \App\Models\PayrollPeriod::STATUSES[$period->status] ?? $period->status }}
                            </span>
                        </td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('payroll.detail', $period) }}" class="btn btn-outline-primary" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </a>
                                @if(!$period->isPaid())
                                <button class="btn btn-outline-danger"
                                    onclick="confirmDelete(() => @this.deletePeriod({{ $period->id }}))"
                                    title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-calendar-check-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada payroll</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white py-2">
            <small class="text-muted">Total: {{ $periods->count() }} payroll</small>
        </div>
    </div>
</div>
