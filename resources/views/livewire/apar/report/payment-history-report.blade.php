<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Tipe</label>
                    <select class="form-select form-select-sm" wire:model.live="reportType">
                        <option value="payable">Pembayaran Hutang</option>
                        <option value="receivable">Penerimaan Piutang</option>
                    </select>
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
                    <label class="form-label small text-muted mb-1">Dari Tanggal</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateFrom">
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Sampai Tanggal</label>
                    <input type="date" class="form-control form-control-sm" wire:model.live="dateTo">
                </div>
            </div>
        </div>
    </div>

    {{-- Summary --}}
    <div class="alert alert-primary py-2 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <span><strong>{{ $label }}</strong> ({{ $payments->count() }} transaksi)</span>
            <strong>Total: Rp {{ number_format($totalAmount, 0, ',', '.') }}</strong>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="3%" class="ps-3">#</th>
                        <th width="10%">Tanggal</th>
                        <th width="12%">No. Faktur</th>
                        <th width="15%">{{ $reportType === 'payable' ? 'Vendor' : 'Pelanggan' }}</th>
                        <th width="12%">Akun</th>
                        <th width="12%">Referensi</th>
                        <th width="12%" class="text-end">Jumlah</th>
                        <th width="15%">Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $idx => $payment)
                    <tr>
                        <td class="ps-3 text-muted">{{ $idx + 1 }}</td>
                        <td class="text-muted small">{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td>
                            @if($reportType === 'payable')
                            <code>{{ $payment->payable->invoice_number }}</code>
                            @else
                            <code>{{ $payment->receivable->invoice_number }}</code>
                            @endif
                        </td>
                        <td>
                            @if($reportType === 'payable')
                            {{ $payment->payable->vendor->name }}
                            @else
                            {{ $payment->receivable->customer->name }}
                            @endif
                        </td>
                        <td class="text-muted small">{{ $payment->paymentCoa->name ?? '-' }}</td>
                        <td class="text-muted small">{{ $payment->reference ?? '-' }}</td>
                        <td class="text-end fw-medium">Rp {{ number_format($payment->amount, 0, ',', '.') }}</td>
                        <td class="text-muted small">{{ Str::limit($payment->notes, 25) ?: '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-file-list-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">Belum ada riwayat pembayaran</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
