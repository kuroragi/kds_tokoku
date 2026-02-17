<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                @if($isSuperAdmin)
                <div class="col-lg-3">
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
                    <label class="form-label small text-muted mb-1">Tahun</label>
                    <select class="form-select form-select-sm" wire:model.live="filterYear">
                        <option value="">Semua</option>
                        @foreach($years as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Bulan</label>
                    <select class="form-select form-select-sm" wire:model.live="filterMonth">
                        <option value="">Semua</option>
                        @foreach($months as $num => $name)
                        <option value="{{ $num }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Periode</div>
                    <div class="fw-bold fs-5">{{ $summary['period_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Penghasilan</div>
                    <div class="fw-bold text-primary">Rp {{ number_format($summary['total_earnings']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">BPJS Perusahaan</div>
                    <div class="fw-bold text-info">Rp {{ number_format($summary['total_benefits']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Potongan</div>
                    <div class="fw-bold text-danger">Rp {{ number_format($summary['total_deductions']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">PPh 21</div>
                    <div class="fw-bold text-warning">Rp {{ number_format($summary['total_tax']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Bersih</div>
                    <div class="fw-bold text-success">Rp {{ number_format($summary['total_net']) }}</div>
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
                        <th width="20%">Periode</th>
                        <th width="12%">Unit</th>
                        <th width="10%">Status</th>
                        <th width="12%" class="text-end">Penghasilan</th>
                        <th width="10%" class="text-end">Benefit</th>
                        <th width="10%" class="text-end">Potongan</th>
                        <th width="8%" class="text-end">PPh 21</th>
                        <th width="13%" class="text-end">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($periods as $i => $period)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                        <td>
                            <a href="{{ route('payroll.detail', $period) }}" class="fw-medium text-decoration-none">
                                {{ $period->name }}
                            </a>
                        </td>
                        <td class="small">{{ $period->businessUnit->name ?? '-' }}</td>
                        <td>
                            <span class="badge bg-{{ \App\Models\PayrollPeriod::STATUS_COLORS[$period->status] ?? 'secondary' }}">
                                {{ \App\Models\PayrollPeriod::STATUSES[$period->status] ?? $period->status }}
                            </span>
                        </td>
                        <td class="text-end">Rp {{ number_format($period->total_earnings) }}</td>
                        <td class="text-end">Rp {{ number_format($period->total_benefits) }}</td>
                        <td class="text-end">Rp {{ number_format($period->total_deductions) }}</td>
                        <td class="text-end">Rp {{ number_format($period->total_tax) }}</td>
                        <td class="text-end fw-semibold text-success">Rp {{ number_format($period->total_net) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            Tidak ada data payroll.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($periods->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="ps-3">Total</td>
                        <td class="text-end">Rp {{ number_format($summary['total_earnings']) }}</td>
                        <td class="text-end">Rp {{ number_format($summary['total_benefits']) }}</td>
                        <td class="text-end">Rp {{ number_format($summary['total_deductions']) }}</td>
                        <td class="text-end">Rp {{ number_format($summary['total_tax']) }}</td>
                        <td class="text-end text-success">Rp {{ number_format($summary['total_net']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
