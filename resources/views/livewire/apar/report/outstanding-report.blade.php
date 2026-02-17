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

    {{-- Partner Cards --}}
    @forelse($data as $partner)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white py-2">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">
                        <i class="ri-{{ $reportType === 'payable' ? 'truck' : 'user-heart' }}-line me-1"></i>
                        {{ $partner['partner_name'] }}
                        <small class="text-muted">({{ $partner['partner_code'] }})</small>
                    </h6>
                </div>
                <div class="text-end">
                    <small class="text-muted">{{ $partner['count'] }} faktur</small>
                    <span class="badge bg-danger ms-2">Sisa: Rp {{ number_format($partner['total_remaining'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">No. Faktur</th>
                        <th>Deskripsi</th>
                        <th>Tgl Faktur</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Dibayar</th>
                        <th class="text-end pe-3">Sisa</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($partner['items'] as $item)
                    <tr>
                        <td class="ps-3"><code>{{ $item->invoice_number }}</code></td>
                        <td class="text-muted small">{{ Str::limit($item->description, 25) ?: '-' }}</td>
                        <td class="text-muted small">{{ $item->invoice_date->format('d/m/Y') }}</td>
                        <td class="small">
                            <span class="{{ $item->is_overdue ? 'text-danger fw-bold' : 'text-muted' }}">
                                {{ $item->due_date->format('d/m/Y') }}
                            </span>
                        </td>
                        <td class="text-end small">Rp {{ number_format($reportType === 'payable' ? $item->amount_due : $item->amount, 0, ',', '.') }}</td>
                        <td class="text-end small text-success">Rp {{ number_format($item->paid_amount, 0, ',', '.') }}</td>
                        <td class="text-end pe-3 fw-medium">Rp {{ number_format($item->remaining, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $statusColors = ['unpaid' => 'warning', 'partial' => 'info'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$item->status] ?? 'secondary' }}">
                                {{ $item->status === 'unpaid' ? 'Belum' : 'Sebagian' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light">
                        <td colspan="4" class="ps-3 fw-bold">Subtotal</td>
                        <td class="text-end fw-bold">Rp {{ number_format($partner['total_amount_due'], 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-success">Rp {{ number_format($partner['total_paid'], 0, ',', '.') }}</td>
                        <td class="text-end pe-3 fw-bold text-danger">Rp {{ number_format($partner['total_remaining'], 0, ',', '.') }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @empty
    <div class="text-center py-5">
        <div class="text-muted">
            <i class="ri-file-list-line" style="font-size: 3rem; opacity: 0.3;"></i>
            <p class="mt-2 mb-0">Tidak ada {{ $reportType === 'payable' ? 'hutang' : 'piutang' }} outstanding</p>
        </div>
    </div>
    @endforelse

    {{-- Grand Total --}}
    @if($data->count() > 0)
    <div class="alert alert-primary py-2">
        <div class="d-flex justify-content-between align-items-center">
            <strong>Grand Total Outstanding</strong>
            <strong>Rp {{ number_format($data->sum('total_remaining'), 0, ',', '.') }}</strong>
        </div>
    </div>
    @endif
</div>
