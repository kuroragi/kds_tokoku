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
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">BPJS Perusahaan</div>
                    <div class="fw-bold text-info">Rp {{ number_format($summary['company_total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">BPJS Karyawan</div>
                    <div class="fw-bold text-warning">Rp {{ number_format($summary['employee_total']) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm text-center">
                <div class="card-body py-3">
                    <div class="text-muted small">Total BPJS</div>
                    <div class="fw-bold text-primary">Rp {{ number_format($summary['grand_total']) }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" width="4%">#</th>
                        <th width="18%">Karyawan</th>
                        <th width="10%">Periode</th>
                        <th width="11%" class="text-end">Gaji Pokok</th>
                        <th width="10%" class="text-end">Kes (C)</th>
                        <th width="8%" class="text-end">JKK</th>
                        <th width="8%" class="text-end">JKM</th>
                        <th width="8%" class="text-end">JHT (C)</th>
                        <th width="8%" class="text-end">JP (C)</th>
                        <th width="8%" class="text-end">Kes (E)</th>
                        <th width="8%" class="text-end">JHT (E)</th>
                        <th width="8%" class="text-end">JP (E)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($grouped as $i => $row)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $loop->iteration }}</td>
                        <td>
                            <div class="fw-medium small">{{ $row['employee']->name ?? '-' }}</div>
                        </td>
                        <td class="small">{{ $row['period']->name ?? '-' }}</td>
                        <td class="text-end small">Rp {{ number_format($row['base_salary']) }}</td>
                        <td class="text-end small">{{ number_format($row['components']['BPJS Kesehatan (Perusahaan)'] ?? 0) }}</td>
                        <td class="text-end small">{{ number_format($row['components']['BPJS JKK'] ?? 0) }}</td>
                        <td class="text-end small">{{ number_format($row['components']['BPJS JKM'] ?? 0) }}</td>
                        <td class="text-end small">{{ number_format($row['components']['BPJS JHT (Perusahaan)'] ?? 0) }}</td>
                        <td class="text-end small">{{ number_format($row['components']['BPJS JP (Perusahaan)'] ?? 0) }}</td>
                        <td class="text-end small text-danger">{{ number_format($row['components']['BPJS Kesehatan (Karyawan)'] ?? 0) }}</td>
                        <td class="text-end small text-danger">{{ number_format($row['components']['BPJS JHT (Karyawan)'] ?? 0) }}</td>
                        <td class="text-end small text-danger">{{ number_format($row['components']['BPJS JP (Karyawan)'] ?? 0) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="12" class="text-center text-muted py-4">
                            Tidak ada data BPJS.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($grouped->count() > 0)
                <tfoot class="table-light fw-bold small">
                    <tr>
                        <td colspan="4" class="ps-3">Total</td>
                        <td colspan="5" class="text-end text-info">Perusahaan: Rp {{ number_format($summary['company_total']) }}</td>
                        <td colspan="3" class="text-end text-danger">Karyawan: Rp {{ number_format($summary['employee_total']) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
