<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
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
                    <label class="form-label small text-muted mb-1">Tahun</label>
                    <select class="form-select form-select-sm" wire:model.live="filterYear">
                        <option value="">Semua</option>
                        @foreach($years as $yr)
                        <option value="{{ $yr }}">{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari Karyawan</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                        <input type="text" class="form-control" wire:model.live.debounce.300ms="filterEmployee"
                            placeholder="Nama / kode karyawan...">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Karyawan</div>
                    <div class="fw-bold fs-5">{{ $summary['employee_count'] }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Penghasilan</div>
                    <div class="fw-bold text-primary">Rp {{ number_format($summary['total_earnings']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Total Potongan</div>
                    <div class="fw-bold text-danger">Rp {{ number_format($summary['total_deductions']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
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
                        <th width="4%" class="ps-3">#</th>
                        <th width="14%">Periode</th>
                        <th width="18%">Karyawan</th>
                        <th width="12%" class="text-end">Gaji Pokok</th>
                        <th width="12%" class="text-end">Penghasilan</th>
                        <th width="10%" class="text-end">Potongan</th>
                        <th width="8%" class="text-end">PPh 21</th>
                        <th width="12%" class="text-end">Gaji Bersih</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $i => $entry)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                        <td class="small">{{ $entry->payrollPeriod->name ?? '-' }}</td>
                        <td>
                            <div class="fw-medium">{{ $entry->employee->name ?? '-' }}</div>
                            <div class="text-muted small">{{ $entry->employee->position->name ?? '' }}</div>
                        </td>
                        <td class="text-end">Rp {{ number_format($entry->base_salary) }}</td>
                        <td class="text-end">Rp {{ number_format($entry->total_earnings) }}</td>
                        <td class="text-end text-danger">Rp {{ number_format($entry->total_deductions) }}</td>
                        <td class="text-end">Rp {{ number_format($entry->pph21_amount) }}</td>
                        <td class="text-end fw-semibold text-success">Rp {{ number_format($entry->net_salary) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            Tidak ada data.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($entries->count() > 0)
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="4" class="ps-3">Total</td>
                        <td class="text-end">Rp {{ number_format($summary['total_earnings']) }}</td>
                        <td class="text-end text-danger">Rp {{ number_format($summary['total_deductions']) }}</td>
                        <td class="text-end">-</td>
                        <td class="text-end text-success">Rp {{ number_format($summary['total_net']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
