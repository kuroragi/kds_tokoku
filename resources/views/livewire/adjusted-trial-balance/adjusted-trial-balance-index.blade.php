<div class="card-body p-0">
    <!-- Filter Controls -->
    <div class="bg-light p-3 border-bottom">
        <form wire:submit="generateReport">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-medium mb-1">Periode</label>
                    <select class="form-select" wire:model="filterPeriod">
                        <option value="">-- Pilih Periode --</option>
                        @foreach($periods as $period)
                        <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 text-center pt-4">
                    <span class="text-muted small">atau</span>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control" wire:model="dateFrom">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-medium mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control" wire:model="dateTo">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ri-search-line"></i> Tampilkan
                    </button>
                    <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters">
                        <i class="ri-filter-off-line"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    @if($showReport && $reportData)
        {{-- Balance Indicators --}}
        <div class="p-3 border-bottom">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="alert py-2 mb-0 {{ $reportData['ns_balanced'] ? 'alert-success' : 'alert-danger' }}">
                        <small>
                            <i class="ri-{{ $reportData['ns_balanced'] ? 'checkbox-circle' : 'error-warning' }}-line me-1"></i>
                            <strong>Neraca Saldo:</strong>
                            {{ $reportData['ns_balanced'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert py-2 mb-0 {{ $reportData['adj_balanced'] ? 'alert-info' : 'alert-danger' }}">
                        <small>
                            <i class="ri-{{ $reportData['adj_balanced'] ? 'checkbox-circle' : 'error-warning' }}-line me-1"></i>
                            <strong>Penyesuaian:</strong>
                            {{ $reportData['adj_balanced'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
                        </small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert py-2 mb-0 {{ $reportData['nsd_balanced'] ? 'alert-success' : 'alert-danger' }}">
                        <small>
                            <i class="ri-{{ $reportData['nsd_balanced'] ? 'checkbox-circle' : 'error-warning' }}-line me-1"></i>
                            <strong>NS Disesuaikan:</strong>
                            {{ $reportData['nsd_balanced'] ? 'SEIMBANG' : 'TIDAK SEIMBANG' }}
                        </small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Worksheet Table --}}
        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered mb-0">
                <thead>
                    {{-- Header Row 1: Group headers --}}
                    <tr class="table-dark text-center">
                        <th rowspan="2" width="6%" class="align-middle">Kode</th>
                        <th rowspan="2" width="18%" class="align-middle">Nama Akun</th>
                        <th colspan="2" width="19%" class="border-start">Neraca Saldo</th>
                        <th colspan="2" width="19%" class="border-start">Penyesuaian</th>
                        <th colspan="2" width="19%" class="border-start">NS Disesuaikan</th>
                    </tr>
                    {{-- Header Row 2: Sub-headers --}}
                    <tr class="table-secondary text-center small">
                        <th class="border-start">Debit</th>
                        <th>Kredit</th>
                        <th class="border-start">Debit</th>
                        <th>Kredit</th>
                        <th class="border-start">Debit</th>
                        <th>Kredit</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $typeColors = [
                            'aktiva' => 'primary',
                            'pasiva' => 'warning',
                            'modal' => 'info',
                            'pendapatan' => 'success',
                            'beban' => 'danger',
                        ];
                        $currentType = '';
                    @endphp

                    @forelse($reportData['accounts'] as $account)
                        {{-- Type separator row --}}
                        @if($currentType !== $account->coa_type)
                            @php $currentType = $account->coa_type; @endphp
                            <tr class="table-light">
                                <td colspan="8" class="fw-bold small py-1">
                                    <span class="badge bg-{{ $typeColors[$currentType] ?? 'secondary' }} me-1">
                                        {{ ucfirst($currentType) }}
                                    </span>
                                </td>
                            </tr>
                        @endif

                        <tr>
                            <td class="text-primary fw-medium small">{{ $account->coa_code }}</td>
                            <td class="small">{{ $account->coa_name }}</td>

                            {{-- Neraca Saldo --}}
                            <td class="text-end small border-start">
                                @if($account->ns_debit > 0)
                                    <span class="text-success">{{ number_format($account->ns_debit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end small">
                                @if($account->ns_credit > 0)
                                    <span class="text-danger">{{ number_format($account->ns_credit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Penyesuaian --}}
                            <td class="text-end small border-start">
                                @if($account->adj_debit > 0)
                                    <span class="text-info fw-medium">{{ number_format($account->adj_debit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end small">
                                @if($account->adj_credit > 0)
                                    <span class="text-info fw-medium">{{ number_format($account->adj_credit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- NS Disesuaikan --}}
                            <td class="text-end small border-start">
                                @if($account->nsd_debit > 0)
                                    <span class="text-success fw-bold">{{ number_format($account->nsd_debit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-end small">
                                @if($account->nsd_credit > 0)
                                    <span class="text-danger fw-bold">{{ number_format($account->nsd_credit, 0, ',', '.') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="ri-file-list-3-line fs-2 d-block mb-2"></i>
                                Tidak ada data neraca penyesuaian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                @if($reportData['accounts']->count() > 0)
                <tfoot>
                    <tr class="table-dark fw-bold text-end">
                        <td colspan="2" class="text-start">Total</td>
                        <td class="border-start">{{ number_format($reportData['total_ns_debit'], 0, ',', '.') }}</td>
                        <td>{{ number_format($reportData['total_ns_credit'], 0, ',', '.') }}</td>
                        <td class="border-start">{{ number_format($reportData['total_adj_debit'], 0, ',', '.') }}</td>
                        <td>{{ number_format($reportData['total_adj_credit'], 0, ',', '.') }}</td>
                        <td class="border-start">{{ number_format($reportData['total_nsd_debit'], 0, ',', '.') }}</td>
                        <td>{{ number_format($reportData['total_nsd_credit'], 0, ',', '.') }}</td>
                    </tr>
                    <tr class="text-center small">
                        <td colspan="2"></td>
                        <td colspan="2" class="border-start">
                            @if($reportData['ns_balanced'])
                                <span class="badge bg-success">SEIMBANG</span>
                            @else
                                <span class="badge bg-danger">TIDAK SEIMBANG</span>
                            @endif
                        </td>
                        <td colspan="2" class="border-start">
                            @if($reportData['adj_balanced'])
                                <span class="badge bg-success">SEIMBANG</span>
                            @else
                                <span class="badge bg-danger">TIDAK SEIMBANG</span>
                            @endif
                        </td>
                        <td colspan="2" class="border-start">
                            @if($reportData['nsd_balanced'])
                                <span class="badge bg-success">SEIMBANG</span>
                            @else
                                <span class="badge bg-danger">TIDAK SEIMBANG</span>
                            @endif
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>

    @elseif(!$showReport)
        {{-- Empty State --}}
        <div class="text-center py-5 text-muted">
            <i class="ri-file-edit-line fs-1 d-block mb-2"></i>
            <h6>Pilih Periode atau Range Tanggal</h6>
            <p class="mb-0 small">Pilih filter di atas lalu klik <strong>Tampilkan</strong> untuk melihat neraca penyesuaian.</p>
        </div>
    @else
        {{-- No Data --}}
        <div class="text-center py-5 text-muted">
            <i class="ri-file-warning-line fs-1 d-block mb-2"></i>
            <h6>Tidak ada data</h6>
            <p class="mb-0 small">Tidak ada jurnal yang sudah diposting pada periode/tanggal yang dipilih.</p>
        </div>
    @endif
</div>
