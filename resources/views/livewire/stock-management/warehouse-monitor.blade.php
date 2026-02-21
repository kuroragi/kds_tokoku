<div>
    {{-- Filter --}}
    <div class="card-body border-bottom py-3">
        <div class="row align-items-end g-3">
            @if($this->isSuperAdmin)
            <div class="col-md-3">
                <label class="form-label small mb-1">Unit Usaha</label>
                <select class="form-select form-select-sm" wire:model.live="business_unit_id">
                    <option value="">-- Semua Unit --</option>
                    @foreach($this->units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            <div class="col-md-3">
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button type="button" class="btn {{ $tab === 'stock' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('tab', 'stock')">
                        <i class="ri-box-3-line me-1"></i> Stok Barang
                    </button>
                    <button type="button" class="btn {{ $tab === 'saldo' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('tab', 'saldo')">
                        <i class="ri-wallet-3-line me-1"></i> Saldo Provider
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    @if($tab === 'stock')
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card border-0 bg-light">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Total Dipantau</div>
                        <div class="fw-bold fs-4">{{ $this->stockSummary['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-success bg-opacity-10">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Stok Normal</div>
                        <div class="fw-bold fs-4 text-success">{{ $this->stockSummary['normal'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-warning bg-opacity-10">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Stok Rendah</div>
                        <div class="fw-bold fs-4 text-warning">{{ $this->stockSummary['low'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 bg-danger bg-opacity-10">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Stok Habis</div>
                        <div class="fw-bold fs-4 text-danger">{{ $this->stockSummary['out_of_stock'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Low Stock Table --}}
        @if($this->lowStockItems->isEmpty())
        <div class="alert alert-success py-3 text-center">
            <i class="ri-checkbox-circle-line fs-4 me-1"></i>
            Semua stok dalam kondisi aman. Tidak ada barang yang mencapai batas minimum.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama Barang</th>
                        <th>Kategori</th>
                        @if($this->isSuperAdmin && !$business_unit_id)
                        <th>Unit</th>
                        @endif
                        <th class="text-end">Stok Saat Ini</th>
                        <th class="text-end">Stok Minimum</th>
                        <th class="text-end">Selisih</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->lowStockItems as $idx => $stock)
                    @php
                        $diff = $stock->current_stock - $stock->min_stock;
                        $isOut = $stock->current_stock <= 0;
                    @endphp
                    <tr class="{{ $isOut ? 'table-danger' : 'table-warning' }}">
                        <td class="text-muted">{{ $idx + 1 }}</td>
                        <td><span class="badge bg-secondary">{{ $stock->code }}</span></td>
                        <td class="fw-semibold">{{ $stock->name }}</td>
                        <td class="text-muted">{{ $stock->categoryGroup?->name ?? '-' }}</td>
                        @if($this->isSuperAdmin && !$business_unit_id)
                        <td><small>{{ $stock->businessUnit?->code }}</small></td>
                        @endif
                        <td class="text-end fw-bold {{ $isOut ? 'text-danger' : 'text-warning' }}">
                            {{ number_format($stock->current_stock, 0) }} {{ $stock->unitOfMeasure?->abbreviation ?? '' }}
                        </td>
                        <td class="text-end text-muted">{{ number_format($stock->min_stock, 0) }}</td>
                        <td class="text-end fw-bold text-danger">{{ number_format($diff, 0) }}</td>
                        <td class="text-center">
                            @if($isOut)
                            <span class="badge bg-danger"><i class="ri-error-warning-line"></i> Habis</span>
                            @else
                            <span class="badge bg-warning text-dark"><i class="ri-alert-line"></i> Rendah</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif

    {{-- Saldo Tab --}}
    @if($tab === 'saldo')
    <div class="card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card border-0 bg-light">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Total Dipantau</div>
                        <div class="fw-bold fs-4">{{ $this->saldoSummary['total'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-success bg-opacity-10">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Saldo Normal</div>
                        <div class="fw-bold fs-4 text-success">{{ $this->saldoSummary['normal'] }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card border-0 bg-warning bg-opacity-10">
                    <div class="card-body py-3 text-center">
                        <div class="text-muted small">Saldo Rendah</div>
                        <div class="fw-bold fs-4 text-warning">{{ $this->saldoSummary['low'] }}</div>
                    </div>
                </div>
            </div>
        </div>

        @if($this->lowBalanceSaldos->isEmpty())
        <div class="alert alert-success py-3 text-center">
            <i class="ri-checkbox-circle-line fs-4 me-1"></i>
            Semua saldo provider dalam kondisi aman. Tidak ada yang mencapai batas minimum.
        </div>
        @else
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Kode</th>
                        <th>Nama Provider</th>
                        <th>Tipe</th>
                        @if($this->isSuperAdmin && !$business_unit_id)
                        <th>Unit</th>
                        @endif
                        <th class="text-end">Saldo Saat Ini</th>
                        <th class="text-end">Saldo Minimum</th>
                        <th class="text-end">Selisih</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($this->lowBalanceSaldos as $idx => $saldo)
                    @php
                        $diff = $saldo->current_balance - $saldo->min_balance;
                    @endphp
                    <tr class="table-warning">
                        <td class="text-muted">{{ $idx + 1 }}</td>
                        <td><span class="badge bg-secondary">{{ $saldo->code }}</span></td>
                        <td class="fw-semibold">{{ $saldo->name }}</td>
                        <td class="text-muted">{{ $saldo->type }}</td>
                        @if($this->isSuperAdmin && !$business_unit_id)
                        <td><small>{{ $saldo->businessUnit?->code }}</small></td>
                        @endif
                        <td class="text-end fw-bold text-warning">Rp {{ number_format($saldo->current_balance, 0, ',', '.') }}</td>
                        <td class="text-end text-muted">Rp {{ number_format($saldo->min_balance, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold text-danger">Rp {{ number_format($diff, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <span class="badge bg-warning text-dark"><i class="ri-alert-line"></i> Rendah</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>
    @endif
</div>
