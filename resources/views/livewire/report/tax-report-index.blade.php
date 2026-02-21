<div>
    {{-- Alert --}}
    <div x-data="{ show: false, type: '', message: '' }"
         x-on:alert.window="show=true; type=$event.detail.type; message=$event.detail.message; setTimeout(()=>show=false, 4000)">
        <div x-show="show" x-transition class="alert alert-dismissible fade show" :class="'alert-'+type" role="alert">
            <span x-text="message"></span>
            <button type="button" class="btn-close" @click="show=false"></button>
        </div>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Laporan Pajak</h4>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" wire:click="openGenerate">
                <i class="fas fa-magic me-1"></i> Generate dari Transaksi
            </button>
            <button class="btn btn-primary btn-sm" wire:click="openCreateFaktur">
                <i class="fas fa-plus me-1"></i> Tambah Faktur
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'faktur' ? 'active' : '' }}" href="#" wire:click.prevent="switchTab('faktur')">
                <i class="fas fa-file-alt me-1"></i> Faktur Pajak
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'spt_masa' ? 'active' : '' }}" href="#" wire:click.prevent="switchTab('spt_masa')">
                <i class="fas fa-calendar-alt me-1"></i> SPT Masa PPN
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $activeTab === 'spt_tahunan' ? 'active' : '' }}" href="#" wire:click.prevent="switchTab('spt_tahunan')">
                <i class="fas fa-calendar me-1"></i> SPT Tahunan
            </a>
        </li>
    </ul>

    {{-- ==================== TAB: FAKTUR PAJAK ==================== --}}
    @if($activeTab === 'faktur')
        {{-- Filters --}}
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    @if($this->isSuperAdmin)
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Unit Bisnis</label>
                        <select class="form-select form-select-sm" wire:model.live="filterUnit">
                            <option value="">Semua Unit</option>
                            @foreach($this->units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Periode</label>
                        <input type="month" class="form-control form-control-sm" wire:model.live="filterPeriod">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Tipe</label>
                        <select class="form-select form-select-sm" wire:model.live="filterType">
                            <option value="">Semua</option>
                            <option value="keluaran">Keluaran</option>
                            <option value="masukan">Masukan</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Status</label>
                        <select class="form-select form-select-sm" wire:model.live="filterStatus">
                            <option value="">Semua</option>
                            <option value="draft">Draft</option>
                            <option value="approved">Approved</option>
                            <option value="reported">Reported</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-0">Cari</label>
                        <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search" placeholder="Nama partner / No. faktur...">
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>No. Faktur</th>
                            <th>Tipe</th>
                            <th>Partner</th>
                            <th>NPWP</th>
                            <th class="text-end">DPP</th>
                            <th class="text-end">PPN</th>
                            <th>Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($this->faktur as $fi)
                        <tr>
                            <td>{{ $fi->invoice_date->format('d/m/Y') }}</td>
                            <td>{{ $fi->faktur_number ?? '-' }}</td>
                            <td>
                                <span class="badge bg-{{ $fi->invoice_type === 'keluaran' ? 'info' : 'warning' }}">
                                    {{ ucfirst($fi->invoice_type) }}
                                </span>
                            </td>
                            <td>{{ $fi->partner_name }}</td>
                            <td><small class="text-muted">{{ $fi->partner_npwp ?? '-' }}</small></td>
                            <td class="text-end">{{ number_format($fi->dpp, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($fi->ppn, 0, ',', '.') }}</td>
                            <td>
                                @php
                                    $statusColors = ['draft'=>'secondary','approved'=>'primary','reported'=>'success','cancelled'=>'danger'];
                                @endphp
                                <span class="badge bg-{{ $statusColors[$fi->status] ?? 'secondary' }}">
                                    {{ ucfirst($fi->status) }}
                                </span>
                            </td>
                            <td class="text-center">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" data-bs-toggle="dropdown"><i class="fas fa-ellipsis-v"></i></button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($fi->status === 'draft')
                                            <li><a class="dropdown-item" href="#" wire:click.prevent="openEditFaktur({{ $fi->id }})"><i class="fas fa-edit me-1"></i> Edit</a></li>
                                            <li><a class="dropdown-item" href="#" wire:click.prevent="changeStatusFaktur({{ $fi->id }}, 'approved')"><i class="fas fa-check me-1"></i> Approve</a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" wire:click.prevent="deleteFaktur({{ $fi->id }})" wire:confirm="Yakin ingin menghapus faktur ini?"><i class="fas fa-trash me-1"></i> Hapus</a></li>
                                        @elseif($fi->status === 'approved')
                                            <li><a class="dropdown-item" href="#" wire:click.prevent="changeStatusFaktur({{ $fi->id }}, 'reported')"><i class="fas fa-paper-plane me-1"></i> Reported</a></li>
                                            <li><a class="dropdown-item text-warning" href="#" wire:click.prevent="changeStatusFaktur({{ $fi->id }}, 'cancelled')"><i class="fas fa-ban me-1"></i> Cancel</a></li>
                                        @elseif($fi->status === 'reported')
                                            <li><a class="dropdown-item text-muted" href="#"><i class="fas fa-info-circle me-1"></i> Sudah Dilaporkan</a></li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-3">Tidak ada faktur pajak.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                {{ $this->faktur->links() }}
            </div>
        </div>
    @endif

    {{-- ==================== TAB: SPT MASA PPN ==================== --}}
    @if($activeTab === 'spt_masa')
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    @if($this->isSuperAdmin)
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Unit Bisnis</label>
                        <select class="form-select form-select-sm" wire:model.live="filterUnit" wire:change="loadSptMasa">
                            <option value="">Semua Unit</option>
                            @foreach($this->units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Periode</label>
                        <input type="month" class="form-control form-control-sm" wire:model.live="sptPeriod">
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($sptMasa))
        <div class="row g-3 mb-3">
            {{-- Pajak Keluaran --}}
            <div class="col-md-4">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-info"><i class="fas fa-arrow-up me-1"></i>PPN Keluaran</h6>
                        <div class="small text-muted">DPP: Rp {{ number_format($sptMasa['keluaran']['dpp'] ?? 0, 0, ',', '.') }}</div>
                        <h4 class="fw-bold text-info mb-0">Rp {{ number_format($sptMasa['keluaran']['ppn'] ?? 0, 0, ',', '.') }}</h4>
                        <div class="small text-muted">{{ $sptMasa['keluaran']['count'] ?? 0 }} faktur</div>
                    </div>
                </div>
            </div>
            {{-- Pajak Masukan --}}
            <div class="col-md-4">
                <div class="card border-warning">
                    <div class="card-body text-center">
                        <h6 class="text-warning"><i class="fas fa-arrow-down me-1"></i>PPN Masukan</h6>
                        <div class="small text-muted">DPP: Rp {{ number_format($sptMasa['masukan']['dpp'] ?? 0, 0, ',', '.') }}</div>
                        <h4 class="fw-bold text-warning mb-0">Rp {{ number_format($sptMasa['masukan']['ppn'] ?? 0, 0, ',', '.') }}</h4>
                        <div class="small text-muted">{{ $sptMasa['masukan']['count'] ?? 0 }} faktur</div>
                    </div>
                </div>
            </div>
            {{-- Kurang/Lebih Bayar --}}
            <div class="col-md-4">
                @php
                    $selisih = ($sptMasa['keluaran']['ppn'] ?? 0) - ($sptMasa['masukan']['ppn'] ?? 0);
                    $isKurang = $selisih >= 0;
                @endphp
                <div class="card border-{{ $isKurang ? 'danger' : 'success' }}">
                    <div class="card-body text-center">
                        <h6 class="text-{{ $isKurang ? 'danger' : 'success' }}">
                            <i class="fas fa-{{ $isKurang ? 'exclamation-triangle' : 'check-circle' }} me-1"></i>
                            {{ $isKurang ? 'Kurang Bayar' : 'Lebih Bayar' }}
                        </h6>
                        <h4 class="fw-bold text-{{ $isKurang ? 'danger' : 'success' }} mb-0">
                            Rp {{ number_format(abs($selisih), 0, ',', '.') }}
                        </h4>
                        <div class="small text-muted">Periode: {{ $sptPeriod }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- SPT Masa Summary Table --}}
        <div class="card">
            <div class="card-header"><strong>Ringkasan SPT Masa PPN</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Uraian</th>
                            <th class="text-end">DPP (Rp)</th>
                            <th class="text-end">PPN (Rp)</th>
                            <th class="text-center">Jumlah Faktur</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><i class="fas fa-arrow-up text-info me-1"></i> PPN Keluaran</td>
                            <td class="text-end">{{ number_format($sptMasa['keluaran']['dpp'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($sptMasa['keluaran']['ppn'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $sptMasa['keluaran']['count'] ?? 0 }}</td>
                        </tr>
                        <tr>
                            <td><i class="fas fa-arrow-down text-warning me-1"></i> PPN Masukan</td>
                            <td class="text-end">{{ number_format($sptMasa['masukan']['dpp'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($sptMasa['masukan']['ppn'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-center">{{ $sptMasa['masukan']['count'] ?? 0 }}</td>
                        </tr>
                        <tr class="table-{{ $isKurang ? 'danger' : 'success' }} fw-bold">
                            <td>{{ $isKurang ? 'PPN Kurang Bayar' : 'PPN Lebih Bayar' }}</td>
                            <td class="text-end">{{ number_format(abs(($sptMasa['keluaran']['dpp'] ?? 0) - ($sptMasa['masukan']['dpp'] ?? 0)), 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format(abs($selisih), 0, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="text-center text-muted py-5">
                <i class="fas fa-calculator fa-3x mb-3"></i>
                <p>Pilih periode untuk melihat SPT Masa PPN.</p>
            </div>
        @endif
    @endif

    {{-- ==================== TAB: SPT TAHUNAN ==================== --}}
    @if($activeTab === 'spt_tahunan')
        <div class="card mb-3">
            <div class="card-body py-2">
                <div class="row g-2 align-items-end">
                    @if($this->isSuperAdmin)
                    <div class="col-md-3">
                        <label class="form-label small mb-0">Unit Bisnis</label>
                        <select class="form-select form-select-sm" wire:model.live="filterUnit" wire:change="loadSptTahunan">
                            <option value="">Semua Unit</option>
                            @foreach($this->units as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-2">
                        <label class="form-label small mb-0">Tahun</label>
                        <input type="number" class="form-control form-control-sm" wire:model.live="sptYear" min="2020" max="2099">
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($sptTahunan))
        {{-- Overview Cards --}}
        <div class="row g-3 mb-3">
            <div class="col-md-3">
                <div class="card border-primary">
                    <div class="card-body text-center">
                        <h6 class="text-primary">Peredaran Usaha</h6>
                        <h5 class="fw-bold mb-0">Rp {{ number_format($sptTahunan['peredaran_usaha'] ?? 0, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-danger">
                    <div class="card-body text-center">
                        <h6 class="text-danger">HPP</h6>
                        <h5 class="fw-bold mb-0">Rp {{ number_format($sptTahunan['hpp'] ?? 0, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-success">
                    <div class="card-body text-center">
                        <h6 class="text-success">Laba Kotor</h6>
                        <h5 class="fw-bold mb-0">Rp {{ number_format($sptTahunan['laba_kotor'] ?? 0, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-info">
                    <div class="card-body text-center">
                        <h6 class="text-info">Total PPN Tahun</h6>
                        @php $totalPpn = collect($sptTahunan['ppn_bulanan'] ?? [])->sum('ppn_keluaran'); @endphp
                        <h5 class="fw-bold mb-0">Rp {{ number_format($totalPpn, 0, ',', '.') }}</h5>
                    </div>
                </div>
            </div>
        </div>

        {{-- PPN Bulanan --}}
        <div class="card mb-3">
            <div class="card-header"><strong>PPN Bulanan {{ $sptYear }}</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bulan</th>
                            <th class="text-end">PPN Keluaran</th>
                            <th class="text-end">PPN Masukan</th>
                            <th class="text-end">Selisih</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sptTahunan['ppn_bulanan'] ?? [] as $row)
                        @php $diff = ($row['ppn_keluaran'] ?? 0) - ($row['ppn_masukan'] ?? 0); @endphp
                        <tr>
                            <td>{{ \Carbon\Carbon::create()->month($row['month'])->translatedFormat('F') }}</td>
                            <td class="text-end">{{ number_format($row['ppn_keluaran'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end">{{ number_format($row['ppn_masukan'] ?? 0, 0, ',', '.') }}</td>
                            <td class="text-end {{ $diff >= 0 ? 'text-danger' : 'text-success' }}">{{ number_format(abs($diff), 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- PPh Badan --}}
        @if(!empty($sptTahunan['pph_badan']))
        <div class="card">
            <div class="card-header"><strong>PPh Badan {{ $sptYear }}</strong></div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td>Laba Komersial</td>
                            <td class="text-end">Rp {{ number_format($sptTahunan['pph_badan']['commercial_profit'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Laba Fiskal</td>
                            <td class="text-end">Rp {{ number_format($sptTahunan['pph_badan']['fiscal_profit'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Penghasilan Kena Pajak</td>
                            <td class="text-end">Rp {{ number_format($sptTahunan['pph_badan']['taxable_income'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                        <tr>
                            <td>Tarif Pajak</td>
                            <td class="text-end">{{ ($sptTahunan['pph_badan']['tax_rate'] ?? 0) * 100 }}%</td>
                        </tr>
                        <tr class="fw-bold table-warning">
                            <td>PPh Terutang</td>
                            <td class="text-end">Rp {{ number_format($sptTahunan['pph_badan']['tax_amount'] ?? 0, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        @else
            <div class="text-center text-muted py-5">
                <i class="fas fa-file-invoice-dollar fa-3x mb-3"></i>
                <p>Pilih tahun untuk melihat SPT Tahunan.</p>
            </div>
        @endif
    @endif

    {{-- ==================== MODAL: FAKTUR FORM ==================== --}}
    @if($showFakturForm)
    <div class="modal-backdrop fade show"></div>
    <div class="modal fade show d-block" tabindex="-1" style="overflow-y: auto">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $isEditingFaktur ? 'Edit' : 'Tambah' }} Faktur Pajak</h5>
                    <button type="button" class="btn-close" wire:click="closeFakturForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipe Faktur <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="faktur_type">
                                <option value="keluaran">Keluaran (Penjualan)</option>
                                <option value="masukan">Masukan (Pembelian)</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">No. Faktur</label>
                            <input type="text" class="form-control" wire:model="faktur_number" placeholder="000.000-00.00000000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Faktur <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" wire:model="faktur_date">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nama Partner <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" wire:model="faktur_partner_name" placeholder="Nama Lawan Transaksi">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">NPWP Partner</label>
                            <input type="text" class="form-control" wire:model="faktur_partner_npwp" placeholder="00.000.000.0-000.000">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">DPP (Dasar Pengenaan Pajak) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" wire:model="faktur_dpp" step="0.01" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PPN</label>
                            <input type="number" class="form-control" wire:model="faktur_ppn" step="0.01" min="0">
                            <small class="text-muted">Kosongkan untuk auto 11%</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">PPnBM</label>
                            <input type="number" class="form-control" wire:model="faktur_ppnbm" step="0.01" min="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" wire:model="faktur_notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeFakturForm">Batal</button>
                    <button type="button" class="btn btn-primary" wire:click="saveFaktur">
                        <i class="fas fa-save me-1"></i> {{ $isEditingFaktur ? 'Perbarui' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ==================== MODAL: GENERATE ==================== --}}
    @if($showGenerateModal)
    <div class="modal-backdrop fade show"></div>
    <div class="modal fade show d-block" tabindex="-1" style="overflow-y: auto">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Faktur dari Transaksi</h5>
                    <button type="button" class="btn-close" wire:click="closeGenerate"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">
                        Generate otomatis faktur pajak dari transaksi penjualan dan pembelian yang memiliki komponen pajak
                        pada periode yang dipilih.
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Periode</label>
                        <input type="month" class="form-control" wire:model="generate_period">
                    </div>
                    <div class="alert alert-info small">
                        <i class="fas fa-info-circle me-1"></i>
                        Transaksi yang sudah memiliki faktur pajak akan dilewatkan secara otomatis.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeGenerate">Batal</button>
                    <button type="button" class="btn btn-primary" wire:click="generateFaktur">
                        <i class="fas fa-magic me-1"></i> Generate
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
