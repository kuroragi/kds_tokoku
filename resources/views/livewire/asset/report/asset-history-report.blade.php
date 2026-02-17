<div>
    {{-- Pilih Aset --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
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
                <div class="col-lg-4">
                    <label class="form-label small text-muted mb-1">Pilih Aset</label>
                    <select class="form-select form-select-sm" wire:model.live="asset_id">
                        <option value="">-- Pilih Aset untuk melihat riwayat --</option>
                        @foreach($assets as $asset)
                        <option value="{{ $asset->id }}">{{ $asset->code }} — {{ $asset->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    @if($assetDetail)
    {{-- Asset Detail Card --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-primary text-white py-2">
            <h6 class="mb-0"><i class="ri-computer-line me-1"></i> {{ $assetDetail['code'] }} — {{ $assetDetail['name'] }}</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-2">
                    <small class="text-muted d-block">Kategori</small>
                    <span class="fw-medium">{{ $assetDetail['category'] }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Tgl Perolehan</small>
                    <span>{{ $assetDetail['acquisition_date'] }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Harga Perolehan</small>
                    <span class="fw-medium text-primary">Rp {{ number_format($assetDetail['acquisition_cost'], 0, ',', '.') }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Akumulasi Penyusutan</small>
                    <span class="text-warning">Rp {{ number_format($assetDetail['accumulated'], 0, ',', '.') }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Nilai Buku</small>
                    <span class="fw-bold text-success">Rp {{ number_format($assetDetail['book_value'], 0, ',', '.') }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Status</small>
                    @php
                        $statusColors = ['active' => 'success', 'disposed' => 'danger', 'under_repair' => 'warning'];
                    @endphp
                    <span class="badge bg-{{ $statusColors[$assetDetail['status']] ?? 'secondary' }}">{{ $assetDetail['status'] }}</span>
                </div>
            </div>
            <div class="row g-3 mt-1">
                <div class="col-md-2">
                    <small class="text-muted d-block">Lokasi</small>
                    <span>{{ $assetDetail['location'] }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Nomor Seri</small>
                    <span>{{ $assetDetail['serial_number'] }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Vendor</small>
                    <span>{{ $assetDetail['vendor'] }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Nilai Residu</small>
                    <span>Rp {{ number_format($assetDetail['salvage_value'], 0, ',', '.') }}</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Masa Manfaat</small>
                    <span>{{ $assetDetail['useful_life_months'] }} bln</span>
                </div>
                <div class="col-md-2">
                    <small class="text-muted d-block">Metode</small>
                    <span>{{ $assetDetail['depreciation_method'] === 'straight_line' ? 'Garis Lurus' : 'Saldo Menurun' }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-2">
            <h6 class="mb-0"><i class="ri-time-line me-1 text-primary"></i> Riwayat Aset ({{ count($timeline) }} event)</h6>
        </div>
        <div class="card-body">
            @if(count($timeline) > 0)
            <div class="timeline-alt pb-0">
                @foreach($timeline as $idx => $event)
                <div class="d-flex mb-3">
                    <div class="me-3 text-center" style="min-width: 90px;">
                        <small class="text-muted d-block">{{ \Carbon\Carbon::parse($event['date'])->format('d/m/Y') }}</small>
                    </div>
                    <div class="flex-shrink-0">
                        <div class="rounded-circle bg-{{ $event['color'] }} bg-opacity-25 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                            <i class="{{ $event['icon'] }} text-{{ $event['color'] }}"></i>
                        </div>
                    </div>
                    <div class="ms-3 flex-grow-1">
                        <h6 class="mb-1">
                            <span class="badge bg-{{ $event['color'] }} bg-opacity-75 me-1">{{ ucfirst($event['type']) }}</span>
                            {{ $event['title'] }}
                        </h6>
                        <p class="text-muted small mb-0">{{ $event['description'] }}</p>
                    </div>
                </div>
                @if(!$loop->last)
                <div class="d-flex mb-3">
                    <div style="min-width: 90px;"></div>
                    <div class="d-flex justify-content-center" style="width: 36px;">
                        <div style="width: 2px; height: 20px; background: #dee2e6;"></div>
                    </div>
                </div>
                @endif
                @endforeach
            </div>
            @else
            <div class="text-center py-5 text-muted">
                <i class="ri-time-line" style="font-size: 3rem; opacity: 0.3;"></i>
                <p class="mt-2 mb-0">Belum ada riwayat untuk aset ini</p>
            </div>
            @endif
        </div>
    </div>

    @else
    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="ri-history-line" style="font-size: 4rem; opacity: 0.3;"></i>
            <p class="mt-3 mb-0">Pilih aset untuk melihat riwayat lengkap</p>
        </div>
    </div>
    @endif
</div>
