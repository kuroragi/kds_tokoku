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
        {{-- Net Income Indicator --}}
        <div class="p-3 border-bottom">
            <div class="alert {{ $reportData['is_profit'] ? 'alert-success' : 'alert-danger' }} py-2 mb-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <i class="ri-{{ $reportData['is_profit'] ? 'arrow-up-circle' : 'arrow-down-circle' }}-line me-1 fs-5"></i>
                        <strong>{{ $reportData['is_profit'] ? 'LABA BERSIH' : 'RUGI BERSIH' }}</strong>
                    </div>
                    <div class="fs-5 fw-bold">
                        Rp {{ number_format(abs($reportData['net_income']), 0, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-0">
            {{-- LEFT: PENDAPATAN --}}
            <div class="col-md-6 border-end">
                <div class="p-3 bg-success bg-opacity-10 border-bottom">
                    <h6 class="mb-0 text-success">
                        <i class="ri-money-dollar-circle-line me-1"></i> PENDAPATAN
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Kode</th>
                                <th width="45%">Nama Akun</th>
                                <th width="35%" class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['pendapatan'] as $account)
                            <tr>
                                <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                <td>{{ $account->coa_name }}</td>
                                <td class="text-end fw-medium text-success">
                                    {{ number_format($account->saldo, 0, ',', '.') }}
                                </td>
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
                                <td class="text-end">{{ number_format($reportData['total_pendapatan'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            {{-- RIGHT: BEBAN --}}
            <div class="col-md-6">
                <div class="p-3 bg-danger bg-opacity-10 border-bottom">
                    <h6 class="mb-0 text-danger">
                        <i class="ri-shopping-cart-line me-1"></i> BEBAN
                    </h6>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="20%">Kode</th>
                                <th width="45%">Nama Akun</th>
                                <th width="35%" class="text-end">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['beban'] as $account)
                            <tr>
                                <td class="text-primary fw-medium">{{ $account->coa_code }}</td>
                                <td>{{ $account->coa_name }}</td>
                                <td class="text-end fw-medium text-danger">
                                    {{ number_format($account->saldo, 0, ',', '.') }}
                                </td>
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
                                <td class="text-end">{{ number_format($reportData['total_beban'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Summary Bar --}}
        <div class="p-3 border-top bg-light">
            <div class="row text-center">
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white">
                        <small class="text-muted d-block">Total Pendapatan</small>
                        <strong class="text-success fs-5">{{ number_format($reportData['total_pendapatan'], 0, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 bg-white">
                        <small class="text-muted d-block">Total Beban</small>
                        <strong class="text-danger fs-5">{{ number_format($reportData['total_beban'], 0, ',', '.') }}</strong>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-2 {{ $reportData['is_profit'] ? 'bg-success' : 'bg-danger' }} bg-opacity-10">
                        <small class="text-muted d-block">{{ $reportData['is_profit'] ? 'Laba Bersih' : 'Rugi Bersih' }}</small>
                        <strong class="{{ $reportData['is_profit'] ? 'text-success' : 'text-danger' }} fs-5">
                            {{ number_format(abs($reportData['net_income']), 0, ',', '.') }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>

    @elseif(!$showReport)
        <div class="text-center py-5 text-muted">
            <i class="ri-line-chart-line fs-1 d-block mb-2"></i>
            <h6>Pilih Periode atau Range Tanggal</h6>
            <p class="mb-0 small">Pilih filter di atas lalu klik <strong>Tampilkan</strong> untuk melihat laporan laba rugi.</p>
        </div>
    @else
        <div class="text-center py-5 text-muted">
            <i class="ri-file-warning-line fs-1 d-block mb-2"></i>
            <h6>Tidak ada data</h6>
            <p class="mb-0 small">Tidak ada jurnal yang sudah diposting pada periode/tanggal yang dipilih.</p>
        </div>
    @endif
</div>
