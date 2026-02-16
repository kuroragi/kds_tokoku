<div class="card-body p-0">
    <!-- Filter Controls -->
    <div class="bg-light p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Periode</label>
                <select class="form-select" wire:model.live="filterPeriod">
                    <option value="">Semua Periode</option>
                    @foreach($periods as $period)
                    <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Dari Tanggal</label>
                <input type="date" class="form-control" wire:model.live="dateFrom">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Sampai Tanggal</label>
                <input type="date" class="form-control" wire:model.live="dateTo">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary" wire:click="clearFilters"
                    title="Bersihkan Filter">
                    <i class="ri-filter-off-line"></i> Reset
                </button>
                <a href="{{ $downloadUrl }}" class="btn btn-outline-danger" target="_blank">
                    <i class="ri-file-pdf-2-line"></i> PDF
                </a>
            </div>
        </div>
    </div>

    <!-- Account Info Header -->
    <div class="p-3 border-bottom bg-white">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-3">
                    <div>
                        <span class="badge bg-primary fs-6 px-3 py-2">{{ $coa->code }}</span>
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $coa->name }}</h5>
                        <small class="text-muted">
                            @php
                                $typeLabels = [
                                    'aktiva' => 'Aktiva',
                                    'pasiva' => 'Pasiva',
                                    'modal' => 'Modal',
                                    'pendapatan' => 'Pendapatan',
                                    'beban' => 'Beban',
                                ];
                                $typeColors = [
                                    'aktiva' => 'primary',
                                    'pasiva' => 'warning',
                                    'modal' => 'info',
                                    'pendapatan' => 'success',
                                    'beban' => 'danger',
                                ];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$coa->type] ?? 'secondary' }}">
                                {{ $typeLabels[$coa->type] ?? ucfirst($coa->type) }}
                            </span>
                            <span class="ms-2">{{ $detailData->count() }} transaksi</span>
                        </small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <div class="d-flex justify-content-end gap-3">
                    <div class="text-center">
                        <small class="text-muted d-block">Total Debit</small>
                        <strong class="text-success">{{ number_format($totalDebit, 0, ',', '.') }}</strong>
                    </div>
                    <div class="text-center">
                        <small class="text-muted d-block">Total Kredit</small>
                        <strong class="text-danger">{{ number_format($totalCredit, 0, ',', '.') }}</strong>
                    </div>
                    <div class="text-center">
                        <small class="text-muted d-block">Saldo</small>
                        <strong class="{{ $finalBalance >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format(abs($finalBalance), 0, ',', '.') }}
                            {{ $finalBalance < 0 ? '(Cr)' : '(Dr)' }}
                        </strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead class="table-dark">
                <tr>
                    <th width="5%" class="text-center">No</th>
                    <th width="10%">Tanggal</th>
                    <th width="13%">No. Jurnal</th>
                    <th width="10%">Referensi</th>
                    <th width="22%">Keterangan</th>
                    <th width="10%">Periode</th>
                    <th width="10%" class="text-end">Debit</th>
                    <th width="10%" class="text-end">Kredit</th>
                    <th width="10%" class="text-end">Saldo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($detailData as $index => $entry)
                <tr>
                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($entry->journal_date)->format('d/m/Y') }}</td>
                    <td>
                        <span class="text-primary fw-medium">{{ $entry->journal_no }}</span>
                    </td>
                    <td>{{ $entry->reference ?? '-' }}</td>
                    <td>{{ $entry->description ?? $entry->journal_description ?? '-' }}</td>
                    <td>
                        <small class="text-muted">{{ $entry->period_name ?? '-' }}</small>
                    </td>
                    <td class="text-end">
                        @if($entry->debit > 0)
                            <span class="text-success fw-medium">{{ number_format($entry->debit, 0, ',', '.') }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($entry->credit > 0)
                            <span class="text-danger fw-medium">{{ number_format($entry->credit, 0, ',', '.') }}</span>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td class="text-end fw-medium">
                        <span class="{{ $entry->running_balance >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format(abs($entry->running_balance), 0, ',', '.') }}
                            {{ $entry->running_balance < 0 ? '(Cr)' : '(Dr)' }}
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-5 text-muted">
                        <i class="ri-file-list-3-line fs-1 d-block mb-2"></i>
                        <h6>Tidak ada transaksi</h6>
                        <p class="mb-0 small">
                            Belum ada transaksi yang diposting untuk akun <strong>{{ $coa->code }} - {{ $coa->name }}</strong>
                            @if($filterPeriod || $dateFrom || $dateTo)
                                pada filter yang dipilih.
                            @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($detailData->count() > 0)
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="6" class="text-end">Total:</td>
                    <td class="text-end text-success">
                        {{ number_format($totalDebit, 0, ',', '.') }}
                    </td>
                    <td class="text-end text-danger">
                        {{ number_format($totalCredit, 0, ',', '.') }}
                    </td>
                    <td class="text-end">
                        <span class="{{ $finalBalance >= 0 ? 'text-dark' : 'text-danger' }}">
                            {{ number_format(abs($finalBalance), 0, ',', '.') }}
                            {{ $finalBalance < 0 ? '(Cr)' : '(Dr)' }}
                        </span>
                    </td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>
