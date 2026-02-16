<div class="card-body p-0">
    <!-- Filter Controls -->
    <div class="bg-light p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Periode</label>
                <select class="form-select" wire:model.live="filterPeriod">
                    <option value="">Semua Periode</option>
                    @foreach($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Tipe Akun</label>
                <select class="form-select" wire:model.live="filterCoaType">
                    <option value="">Semua Tipe</option>
                    @foreach($coaTypes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Akun</label>
                <select class="form-select" wire:model.live="filterCoa">
                    <option value="">Semua Akun</option>
                    @foreach($coas as $coa)
                    <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Dari Tanggal</label>
                <input type="date" class="form-control" wire:model.live="dateFrom">
            </div>
            <div class="col-md-2">
                <label class="form-label small fw-medium mb-1">Sampai Tanggal</label>
                <input type="date" class="form-control" wire:model.live="dateTo">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-secondary w-100" wire:click="clearFilters"
                    title="Bersihkan Filter">
                    <i class="ri-filter-off-line"></i>
                </button>
            </div>
        </div>
        <div class="mt-2 text-end">
            <a href="{{ $downloadUrl }}" class="btn btn-sm btn-outline-danger" target="_blank">
                <i class="ri-file-pdf-2-line me-1"></i> Download PDF
            </a>
        </div>
    </div>

    <!-- Summary Table -->
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th width="10%">Kode</th>
                    <th width="25%">Nama Akun</th>
                    <th width="12%">Tipe</th>
                    <th width="10%" class="text-center">Transaksi</th>
                    <th width="15%" class="text-end">Total Debit</th>
                    <th width="15%" class="text-end">Total Kredit</th>
                    <th width="8%" class="text-end">Saldo</th>
                    <th width="5%" class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($summaryData as $row)
                <tr class="align-middle">
                    <td>
                        <span class="fw-medium text-primary">{{ $row->coa_code }}</span>
                    </td>
                    <td>{{ $row->coa_name }}</td>
                    <td>
                        @php
                            $typeColors = [
                                'aktiva' => 'primary',
                                'pasiva' => 'warning',
                                'modal' => 'info',
                                'pendapatan' => 'success',
                                'beban' => 'danger',
                            ];
                        @endphp
                        <span class="badge bg-{{ $typeColors[$row->coa_type] ?? 'secondary' }}">
                            {{ ucfirst($row->coa_type) }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark">{{ $row->total_transactions }}</span>
                    </td>
                    <td class="text-end text-success">
                        {{ number_format($row->total_debit, 0, ',', '.') }}
                    </td>
                    <td class="text-end text-danger">
                        {{ number_format($row->total_credit, 0, ',', '.') }}
                    </td>
                    <td class="text-end fw-medium">
                        @php $balance = $row->total_debit - $row->total_credit; @endphp
                        <span class="{{ $balance >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format(abs($balance), 0, ',', '.') }}
                            {{ $balance < 0 ? '(Cr)' : '(Dr)' }}
                        </span>
                    </td>
                    <td class="text-center">
                        <a href="{{ route('general-ledger.detail', $row->coa_id) }}"
                            class="btn btn-sm btn-outline-primary" title="Lihat Detail">
                            <i class="ri-eye-line"></i>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="ri-book-open-line fs-1 d-block mb-2"></i>
                        <h6>Belum ada data Buku Besar</h6>
                        <p class="mb-0 small">Data akan muncul setelah ada jurnal yang sudah diposting.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($summaryData->count() > 0)
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Grand Total:</td>
                    <td class="text-end text-success">
                        {{ number_format($grandTotalDebit, 0, ',', '.') }}
                    </td>
                    <td class="text-end text-danger">
                        {{ number_format($grandTotalCredit, 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                        @php $grandBalance = $grandTotalDebit - $grandTotalCredit; @endphp
                        <span class="{{ $grandBalance >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format(abs($grandBalance), 0, ',', '.') }}
                            {{ $grandBalance < 0 ? '(Cr)' : '(Dr)' }}
                        </span>
                    </td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>

    @if($summaryData->count() > 0)
    <div class="p-3 border-top">
        <div class="row text-center">
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <small class="text-muted d-block">Total Akun</small>
                    <strong>{{ $summaryData->count() }}</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <small class="text-muted d-block">Total Transaksi</small>
                    <strong>{{ $summaryData->sum('total_transactions') }}</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <small class="text-muted d-block">Total Debit</small>
                    <strong class="text-success">{{ number_format($grandTotalDebit, 0, ',', '.') }}</strong>
                </div>
            </div>
            <div class="col-md-3">
                <div class="border rounded p-2">
                    <small class="text-muted d-block">Total Kredit</small>
                    <strong class="text-danger">{{ number_format($grandTotalCredit, 0, ',', '.') }}</strong>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
