<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Tipe</label>
                    <select class="form-select form-select-sm" wire:model.live="reportType">
                        <option value="payable">Hutang (AP)</option>
                        <option value="receivable">Piutang (AR)</option>
                    </select>
                </div>
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
            </div>
        </div>
    </div>

    {{-- Aging Summary --}}
    <div class="row g-3 mb-3">
        @foreach($bucketLabels as $key => $label)
        @php
            $colors = ['current' => 'success', '1_30' => 'warning', '31_60' => 'orange', '61_90' => 'danger', 'over_90' => 'dark'];
            $color = $colors[$key] ?? 'secondary';
            $count = $aging[$key]['items']->count();
            $total = $aging[$key]['total'];
        @endphp
        <div class="col-lg">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body py-3 text-center">
                    <small class="text-muted d-block">{{ $label }}</small>
                    <h5 class="mb-1 text-{{ $color }}">Rp {{ number_format($total, 0, ',', '.') }}</h5>
                    <small class="text-muted">{{ $count }} transaksi</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    {{-- Grand Total --}}
    <div class="alert alert-primary py-2 mb-3">
        <div class="d-flex justify-content-between align-items-center">
            <span><strong>Total {{ $reportType === 'payable' ? 'Hutang' : 'Piutang' }} Outstanding</strong></span>
            <strong>Rp {{ number_format($grandTotal, 0, ',', '.') }}</strong>
        </div>
    </div>

    {{-- Detail Table --}}
    @foreach($bucketLabels as $key => $label)
    @if($aging[$key]['items']->count() > 0)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0">
                <i class="ri-time-line me-1"></i> {{ $label }}
                <span class="badge bg-secondary ms-2">{{ $aging[$key]['items']->count() }}</span>
            </h6>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. Faktur</th>
                        <th>{{ $reportType === 'payable' ? 'Vendor' : 'Pelanggan' }}</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-end pe-3">Sisa</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($aging[$key]['items'] as $item)
                    <tr>
                        <td class="ps-3"><code>{{ $item->invoice_number }}</code></td>
                        <td>{{ $reportType === 'payable' ? $item->vendor->name : $item->customer->name }}</td>
                        <td class="text-muted small">{{ $item->due_date->format('d/m/Y') }}</td>
                        <td class="text-end pe-3 fw-medium">Rp {{ number_format($item->remaining, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="3" class="ps-3 fw-bold">Subtotal</td>
                        <td class="text-end pe-3 fw-bold">Rp {{ number_format($aging[$key]['total'], 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif
    @endforeach
</div>
