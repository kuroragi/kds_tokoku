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

    @if($showReport && $balanceSheetData && $incomeStatementData)
        {{-- Download Button --}}
        <div class="px-3 pt-3 d-flex justify-content-end">
            <a href="{{ $downloadUrl }}" class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="ri-file-pdf-2-line me-1"></i> Download PDF
            </a>
        </div>

        {{-- ===== SECTION 1: NERACA (Balance Sheet) ===== --}}
        <div class="px-3 pt-3">
            <h6 class="fw-bold text-uppercase text-primary border-bottom pb-2 mb-3">
                <i class="ri-scales-3-line me-1"></i> I. Neraca (Balance Sheet)
            </h6>

            {{-- Balance status indicator --}}
            <div class="alert {{ $balanceSheetData['is_balanced'] ? 'alert-success' : 'alert-danger' }} py-2 mb-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="ri-{{ $balanceSheetData['is_balanced'] ? 'checkbox-circle' : 'error-warning' }}-line me-1"></i>
                        {{ $balanceSheetData['is_balanced'] ? 'Neraca SEIMBANG' : 'Neraca TIDAK SEIMBANG' }}
                    </div>
                    <div>
                        <strong>Aktiva:</strong> Rp {{ number_format($balanceSheetData['total_aktiva'], 0, ',', '.') }}
                        &nbsp;|&nbsp;
                        <strong>Pasiva+Modal+L/R:</strong> Rp {{ number_format($balanceSheetData['total_pasiva_modal_laba'], 0, ',', '.') }}
                    </div>
                </div>
            </div>

            <div class="row">
                {{-- LEFT SIDE: AKTIVA --}}
                <div class="col-md-6">
                    <div class="card border mb-3">
                        <div class="card-header bg-primary text-white py-2">
                            <h6 class="mb-0"><i class="ri-safe-2-line me-1"></i> AKTIVA (Harta)</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Kode</th>
                                        <th width="50%">Nama Akun</th>
                                        <th width="30%" class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($balanceSheetData['aktiva'] as $account)
                                    <tr>
                                        <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                        <td>{{ $account->coa_name }}</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($account->saldo, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada data aktiva</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-primary">
                                    <tr class="fw-bold">
                                        <td colspan="2">Total Aktiva</td>
                                        <td class="text-end">Rp {{ number_format($balanceSheetData['total_aktiva'], 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- RIGHT SIDE: PASIVA + MODAL --}}
                <div class="col-md-6">
                    {{-- Pasiva --}}
                    <div class="card border mb-3">
                        <div class="card-header bg-warning text-dark py-2">
                            <h6 class="mb-0"><i class="ri-hand-coin-line me-1"></i> PASIVA (Kewajiban)</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Kode</th>
                                        <th width="50%">Nama Akun</th>
                                        <th width="30%" class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($balanceSheetData['pasiva'] as $account)
                                    <tr>
                                        <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                        <td>{{ $account->coa_name }}</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($account->saldo, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada data pasiva</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-warning">
                                    <tr class="fw-bold">
                                        <td colspan="2">Total Pasiva</td>
                                        <td class="text-end">Rp {{ number_format($balanceSheetData['total_pasiva'], 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Modal --}}
                    <div class="card border mb-3">
                        <div class="card-header bg-info text-white py-2">
                            <h6 class="mb-0"><i class="ri-funds-line me-1"></i> MODAL (Ekuitas)</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Kode</th>
                                        <th width="50%">Nama Akun</th>
                                        <th width="30%" class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($balanceSheetData['modal'] as $account)
                                    <tr>
                                        <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                        <td>{{ $account->coa_name }}</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($account->saldo, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada data modal</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-info">
                                    <tr class="fw-bold">
                                        <td colspan="2">Total Modal</td>
                                        <td class="text-end">Rp {{ number_format($balanceSheetData['total_modal'], 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Laba/Rugi --}}
                    <div class="card border mb-3">
                        <div class="card-header {{ $balanceSheetData['laba_rugi'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white py-2">
                            <h6 class="mb-0">
                                <i class="ri-line-chart-line me-1"></i>
                                {{ $balanceSheetData['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                            </h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr class="fw-bold {{ $balanceSheetData['laba_rugi'] >= 0 ? 'table-success' : 'table-danger' }}">
                                        <td>{{ $balanceSheetData['laba_rugi'] >= 0 ? 'Laba Bersih Periode Ini' : 'Rugi Bersih Periode Ini' }}</td>
                                        <td class="text-end">Rp {{ number_format(abs($balanceSheetData['laba_rugi']), 0, ',', '.') }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Grand Total --}}
                    <div class="card border">
                        <div class="card-body py-2 bg-dark text-white">
                            <div class="d-flex justify-content-between align-items-center fw-bold">
                                <span>Total Pasiva + Modal + Laba/Rugi</span>
                                <span>Rp {{ number_format($balanceSheetData['total_pasiva_modal_laba'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== SECTION 2: LABA RUGI (Income Statement) ===== --}}
        <div class="px-3 pt-4">
            <h6 class="fw-bold text-uppercase text-primary border-bottom pb-2 mb-3">
                <i class="ri-line-chart-line me-1"></i> II. Laporan Laba Rugi (Income Statement)
            </h6>

            <div class="row">
                {{-- Pendapatan --}}
                <div class="col-md-6">
                    <div class="card border mb-3">
                        <div class="card-header bg-success text-white py-2">
                            <h6 class="mb-0"><i class="ri-money-dollar-circle-line me-1"></i> PENDAPATAN</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Kode</th>
                                        <th width="50%">Nama Akun</th>
                                        <th width="30%" class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($incomeStatementData['pendapatan'] as $account)
                                    <tr>
                                        <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                        <td>{{ $account->coa_name }}</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($account->saldo, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada data pendapatan</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-success">
                                    <tr class="fw-bold">
                                        <td colspan="2">Total Pendapatan</td>
                                        <td class="text-end">Rp {{ number_format($incomeStatementData['total_pendapatan'], 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Beban --}}
                <div class="col-md-6">
                    <div class="card border mb-3">
                        <div class="card-header bg-danger text-white py-2">
                            <h6 class="mb-0"><i class="ri-money-dollar-box-line me-1"></i> BEBAN</h6>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th width="20%">Kode</th>
                                        <th width="50%">Nama Akun</th>
                                        <th width="30%" class="text-end">Saldo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($incomeStatementData['beban'] as $account)
                                    <tr>
                                        <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                        <td>{{ $account->coa_name }}</td>
                                        <td class="text-end fw-medium">Rp {{ number_format($account->saldo, 0, ',', '.') }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-3">Tidak ada data beban</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="table-danger">
                                    <tr class="fw-bold">
                                        <td colspan="2">Total Beban</td>
                                        <td class="text-end">Rp {{ number_format($incomeStatementData['total_beban'], 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Net Income Summary --}}
            <div class="card border mb-3">
                <div class="card-body {{ $incomeStatementData['is_profit'] ? 'bg-success' : 'bg-danger' }} bg-opacity-10">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="small text-muted">Total Pendapatan</div>
                            <div class="fw-bold text-success fs-5">Rp {{ number_format($incomeStatementData['total_pendapatan'], 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">Total Beban</div>
                            <div class="fw-bold text-danger fs-5">Rp {{ number_format($incomeStatementData['total_beban'], 0, ',', '.') }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">{{ $incomeStatementData['is_profit'] ? 'Laba Bersih' : 'Rugi Bersih' }}</div>
                            <div class="fw-bold {{ $incomeStatementData['is_profit'] ? 'text-success' : 'text-danger' }} fs-5">
                                Rp {{ number_format(abs($incomeStatementData['net_income']), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== SECTION 3: RINGKASAN POSISI KEUANGAN ===== --}}
        <div class="px-3 pt-3 pb-3">
            <h6 class="fw-bold text-uppercase text-primary border-bottom pb-2 mb-3">
                <i class="ri-pie-chart-line me-1"></i> III. Ringkasan Posisi Keuangan
            </h6>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card border-start border-primary border-3 h-100">
                        <div class="card-body text-center">
                            <div class="small text-muted mb-1">Total Aktiva</div>
                            <div class="fw-bold text-primary fs-5">Rp {{ number_format($balanceSheetData['total_aktiva'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-warning border-3 h-100">
                        <div class="card-body text-center">
                            <div class="small text-muted mb-1">Total Kewajiban</div>
                            <div class="fw-bold text-warning fs-5">Rp {{ number_format($balanceSheetData['total_pasiva'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start border-info border-3 h-100">
                        <div class="card-body text-center">
                            <div class="small text-muted mb-1">Total Ekuitas</div>
                            <div class="fw-bold text-info fs-5">Rp {{ number_format($balanceSheetData['total_modal'], 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-start {{ $incomeStatementData['is_profit'] ? 'border-success' : 'border-danger' }} border-3 h-100">
                        <div class="card-body text-center">
                            <div class="small text-muted mb-1">{{ $incomeStatementData['is_profit'] ? 'Laba Bersih' : 'Rugi Bersih' }}</div>
                            <div class="fw-bold {{ $incomeStatementData['is_profit'] ? 'text-success' : 'text-danger' }} fs-5">
                                Rp {{ number_format(abs($incomeStatementData['net_income']), 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Persamaan Akuntansi --}}
            <div class="card bg-light mt-3">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-center align-items-center gap-3 flex-wrap">
                        <div class="text-center">
                            <div class="small text-muted">Aktiva</div>
                            <div class="fw-bold">Rp {{ number_format($balanceSheetData['total_aktiva'], 0, ',', '.') }}</div>
                        </div>
                        <div class="fs-4 text-muted">=</div>
                        <div class="text-center">
                            <div class="small text-muted">Kewajiban</div>
                            <div class="fw-bold">Rp {{ number_format($balanceSheetData['total_pasiva'], 0, ',', '.') }}</div>
                        </div>
                        <div class="fs-4 text-muted">+</div>
                        <div class="text-center">
                            <div class="small text-muted">Ekuitas</div>
                            <div class="fw-bold">Rp {{ number_format($balanceSheetData['total_modal'], 0, ',', '.') }}</div>
                        </div>
                        <div class="fs-4 text-muted">+</div>
                        <div class="text-center">
                            <div class="small text-muted">{{ $incomeStatementData['is_profit'] ? 'Laba' : 'Rugi' }}</div>
                            <div class="fw-bold">Rp {{ number_format(abs($incomeStatementData['net_income']), 0, ',', '.') }}</div>
                        </div>
                        <div class="ms-3">
                            @if($balanceSheetData['is_balanced'])
                                <span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i> BALANCE</span>
                            @else
                                <span class="badge bg-danger"><i class="ri-error-warning-line me-1"></i> TIDAK BALANCE</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @elseif(!$showReport)
        <div class="text-center py-5 text-muted">
            <i class="ri-file-chart-line" style="font-size: 3rem;"></i>
            <p class="mt-2 mb-0">Pilih periode atau range tanggal, lalu klik <strong>Tampilkan</strong> untuk melihat Neraca Keuangan Final.</p>
        </div>
    @endif
</div>
