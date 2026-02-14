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

    @if($showReport && $reportData && $trialBalanceData)
        {{-- ===== Tab Navigation ===== --}}
        <ul class="nav nav-tabs px-3 pt-3" id="neracaTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="neraca-tab" data-bs-toggle="tab" data-bs-target="#neraca-pane"
                    type="button" role="tab">
                    <i class="ri-scales-3-line me-1"></i> Neraca (Balance Sheet)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="saldo-tab" data-bs-toggle="tab" data-bs-target="#saldo-pane"
                    type="button" role="tab">
                    <i class="ri-file-list-3-line me-1"></i> Neraca Saldo (Trial Balance)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="neracaTabContent">
            {{-- ===== TAB 1: NERACA (Balance Sheet) ===== --}}
            <div class="tab-pane fade show active p-3" id="neraca-pane" role="tabpanel">
                {{-- Balance status indicator --}}
                <div class="alert {{ $reportData['is_balanced'] ? 'alert-success' : 'alert-danger' }} py-2 mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <i class="ri-{{ $reportData['is_balanced'] ? 'checkbox-circle' : 'error-warning' }}-line me-1"></i>
                            {{ $reportData['is_balanced'] ? 'Neraca SEIMBANG' : 'Neraca TIDAK SEIMBANG' }}
                        </div>
                        <div>
                            <strong>Aktiva:</strong> {{ number_format($reportData['total_aktiva'], 0, ',', '.') }}
                            &nbsp;|&nbsp;
                            <strong>Pasiva+Modal+L/R:</strong> {{ number_format($reportData['total_pasiva_modal_laba'], 0, ',', '.') }}
                        </div>
                    </div>
                </div>

                <div class="row">
                    {{-- LEFT SIDE: AKTIVA --}}
                    <div class="col-md-6">
                        <div class="card border mb-3">
                            <div class="card-header bg-primary text-white py-2">
                                <h6 class="mb-0"><i class="ri-safe-2-line me-1"></i> AKTIVA</h6>
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
                                        @forelse($reportData['aktiva'] as $account)
                                        <tr>
                                            <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                            <td>{{ $account->coa_name }}</td>
                                            <td class="text-end fw-medium">{{ number_format($account->saldo, 0, ',', '.') }}</td>
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
                                            <td class="text-end">{{ number_format($reportData['total_aktiva'], 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT SIDE: PASIVA + MODAL + LABA/RUGI --}}
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
                                        @forelse($reportData['pasiva'] as $account)
                                        <tr>
                                            <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                            <td>{{ $account->coa_name }}</td>
                                            <td class="text-end fw-medium">{{ number_format($account->saldo, 0, ',', '.') }}</td>
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
                                            <td class="text-end">{{ number_format($reportData['total_pasiva'], 0, ',', '.') }}</td>
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
                                        @forelse($reportData['modal'] as $account)
                                        <tr>
                                            <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                            <td>{{ $account->coa_name }}</td>
                                            <td class="text-end fw-medium">{{ number_format($account->saldo, 0, ',', '.') }}</td>
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
                                            <td class="text-end">{{ number_format($reportData['total_modal'], 0, ',', '.') }}</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        {{-- Laba/Rugi --}}
                        <div class="card border mb-3">
                            <div class="card-header {{ $reportData['laba_rugi'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white py-2">
                                <h6 class="mb-0">
                                    <i class="ri-line-chart-line me-1"></i>
                                    {{ $reportData['laba_rugi'] >= 0 ? 'LABA BERSIH' : 'RUGI BERSIH' }}
                                </h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <tbody>
                                        <tr class="fw-bold">
                                            <td width="70%">
                                                {{ $reportData['laba_rugi'] >= 0 ? 'Laba Bersih Periode Ini' : 'Rugi Bersih Periode Ini' }}
                                                <a href="{{ route('income-statement') }}" class="ms-2 small text-decoration-none">
                                                    <i class="ri-external-link-line"></i> Lihat Rincian
                                                </a>
                                            </td>
                                            <td width="30%" class="text-end {{ $reportData['laba_rugi'] >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ number_format(abs($reportData['laba_rugi']), 0, ',', '.') }}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Grand Total Right Side --}}
                        <div class="card border border-dark">
                            <div class="card-body py-2 bg-dark text-white">
                                <div class="d-flex justify-content-between align-items-center fw-bold">
                                    <span>Total Pasiva + Modal + Laba/Rugi</span>
                                    <span>{{ number_format($reportData['total_pasiva_modal_laba'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== TAB 2: NERACA SALDO (Trial Balance) ===== --}}
            <div class="tab-pane fade p-0" id="saldo-pane" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th width="10%">Kode</th>
                                <th width="30%">Nama Akun</th>
                                <th width="12%">Tipe</th>
                                <th width="16%" class="text-end">Total Debit</th>
                                <th width="16%" class="text-end">Total Kredit</th>
                                <th width="16%" class="text-end">Saldo</th>
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
                            @endphp
                            @forelse($trialBalanceData['accounts'] as $account)
                            <tr>
                                <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                <td>{{ $account->coa_name }}</td>
                                <td>
                                    <span class="badge bg-{{ $typeColors[$account->coa_type] ?? 'secondary' }}">
                                        {{ ucfirst($account->coa_type) }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($account->saldo_debit > 0)
                                        <span class="text-success fw-medium">{{ number_format($account->saldo_debit, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($account->saldo_credit > 0)
                                        <span class="text-danger fw-medium">{{ number_format($account->saldo_credit, 0, ',', '.') }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-end fw-medium">
                                    @php $bal = $account->total_debit - $account->total_credit; @endphp
                                    <span class="{{ $bal >= 0 ? 'text-dark' : 'text-danger' }}">
                                        {{ number_format(abs($bal), 0, ',', '.') }}
                                        {{ $bal < 0 ? '(Cr)' : '(Dr)' }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    <i class="ri-file-list-3-line fs-2 d-block mb-2"></i>
                                    Tidak ada data neraca saldo.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($trialBalanceData['accounts']->count() > 0)
                        <tfoot class="table-light">
                            <tr class="fw-bold">
                                <td colspan="3" class="text-end">Total:</td>
                                <td class="text-end text-success">{{ number_format($trialBalanceData['total_debit'], 0, ',', '.') }}</td>
                                <td class="text-end text-danger">{{ number_format($trialBalanceData['total_credit'], 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @if(abs($trialBalanceData['total_debit'] - $trialBalanceData['total_credit']) < 0.01)
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
            </div>
        </div>

    @elseif(!$showReport)
        {{-- Empty State --}}
        <div class="text-center py-5 text-muted">
            <i class="ri-scales-3-line fs-1 d-block mb-2"></i>
            <h6>Pilih Periode atau Range Tanggal</h6>
            <p class="mb-0 small">Pilih filter di atas lalu klik <strong>Tampilkan</strong> untuk melihat neraca saldo.</p>
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
