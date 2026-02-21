<div>
    {{-- Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                        placeholder="Kode / Nama proyek...">
                </div>
                @if($this->isSuperAdmin)
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua</option>
                        @foreach($this->units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua</option>
                        @foreach(\App\Models\Project::STATUSES as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-5 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="openCreate">
                        <i class="ri-add-line"></i> Buat Proyek
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="4%">#</th>
                        <th width="12%">Kode</th>
                        <th width="18%">Nama Proyek</th>
                        <th width="12%">Customer</th>
                        <th width="10%">Periode</th>
                        <th width="10%" class="text-end">Anggaran</th>
                        <th width="10%" class="text-end">Realisasi</th>
                        <th width="10%">Progress</th>
                        <th width="6%" class="text-center">Status</th>
                        <th width="8%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($this->projects as $idx => $p)
                    <tr wire:key="prj-{{ $p->id }}">
                        <td class="text-muted ps-3">{{ $this->projects->firstItem() + $idx }}</td>
                        <td class="fw-semibold">{{ $p->project_code }}</td>
                        <td>
                            {{ $p->name }}
                            @if($p->description)
                            <br><small class="text-muted">{{ Str::limit($p->description, 40) }}</small>
                            @endif
                        </td>
                        <td><small>{{ $p->customer?->name ?? '-' }}</small></td>
                        <td>
                            <small>{{ $p->start_date->format('d/m/Y') }}</small>
                            @if($p->end_date)
                            <br><small class="text-muted">s/d {{ $p->end_date->format('d/m/Y') }}</small>
                            @endif
                        </td>
                        <td class="text-end small">Rp {{ number_format($p->budget, 0, ',', '.') }}</td>
                        <td class="text-end small {{ $p->actual_cost > $p->budget && $p->budget > 0 ? 'text-danger fw-bold' : '' }}">
                            Rp {{ number_format($p->actual_cost, 0, ',', '.') }}
                        </td>
                        <td>
                            @if($p->budget > 0)
                            <div class="d-flex align-items-center gap-1">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar {{ $p->budget_usage > 100 ? 'bg-danger' : 'bg-success' }}"
                                        style="width: {{ min($p->budget_usage, 100) }}%"></div>
                                </div>
                                <small class="text-muted">{{ $p->budget_usage }}%</small>
                            </div>
                            @else
                            <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @php
                                $stColors = ['planning' => 'secondary', 'active' => 'success', 'on_hold' => 'warning', 'completed' => 'primary', 'cancelled' => 'danger'];
                            @endphp
                            <span class="badge bg-{{ $stColors[$p->status] ?? 'secondary' }}">
                                {{ $p->status_label }}
                            </span>
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="openDetail({{ $p->id }})" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <button class="btn btn-outline-secondary" wire:click="openEdit({{ $p->id }})" title="Edit">
                                    <i class="ri-pencil-line"></i>
                                </button>
                                @if(in_array($p->status, ['planning', 'cancelled']))
                                <button class="btn btn-outline-danger" wire:click="deleteProject({{ $p->id }})"
                                    wire:confirm="Hapus proyek ini?" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="text-center text-muted py-4">
                            <i class="ri-briefcase-line fs-3 d-block mb-2"></i>
                            Belum ada proyek / job order.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($this->projects->hasPages())
        <div class="card-footer bg-white border-top px-3 py-2">{{ $this->projects->links() }}</div>
        @endif
    </div>

    {{-- ═══════════════ CREATE / EDIT MODAL ═══════════════ --}}
    @if($showFormModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-lg" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-briefcase-line me-1"></i>
                        {{ $isEditing ? 'Edit Proyek' : 'Buat Proyek Baru' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nama Proyek <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                wire:model="name" placeholder="Nama proyek / job order">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Customer</label>
                            <select class="form-select" wire:model="customer_id">
                                <option value="">-- Pilih Customer --</option>
                                @foreach($this->customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" wire:model="description" rows="2" placeholder="Deskripsi proyek (opsional)"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" wire:model="start_date">
                            @error('start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Tanggal Selesai</label>
                            <input type="date" class="form-control" wire:model="end_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Anggaran <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('budget') is-invalid @enderror"
                                    wire:model="budget" step="0.01">
                            </div>
                            @error('budget') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        @if($isEditing)
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select class="form-select" wire:model="status">
                                @foreach(\App\Models\Project::STATUSES as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control" wire:model="notes" rows="2" placeholder="Catatan (opsional)"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeForm">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="saveProject" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveProject"><i class="ri-check-line"></i> {{ $isEditing ? 'Perbarui' : 'Simpan' }}</span>
                        <span wire:loading wire:target="saveProject"><i class="ri-loader-4-line"></i> Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ DETAIL MODAL ═══════════════ --}}
    @if($showDetailModal && $detailProject)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-xl" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-briefcase-line me-1"></i>
                        {{ $detailProject->project_code }} — {{ $detailProject->name }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeDetail"></button>
                </div>
                <div class="modal-body">
                    {{-- Summary Cards --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-2">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Anggaran</div>
                                    <div class="fw-bold text-primary">Rp {{ number_format($summary['budget'] ?? 0, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Realisasi Biaya</div>
                                    <div class="fw-bold {{ ($summary['budget_usage'] ?? 0) > 100 ? 'text-danger' : '' }}">
                                        Rp {{ number_format($summary['actual_cost'] ?? 0, 0, ',', '.') }}
                                    </div>
                                    <small class="text-muted">{{ $summary['budget_usage'] ?? 0 }}%</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Pendapatan</div>
                                    <div class="fw-bold text-success">Rp {{ number_format($summary['revenue'] ?? 0, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border-0 {{ ($summary['profit'] ?? 0) >= 0 ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Laba/Rugi</div>
                                    <div class="fw-bold {{ ($summary['profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        Rp {{ number_format($summary['profit'] ?? 0, 0, ',', '.') }}
                                    </div>
                                    <small class="text-muted">Margin: {{ $summary['profit_margin'] ?? 0 }}%</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Status</div>
                                    @php $stC = ['planning'=>'secondary','active'=>'success','on_hold'=>'warning','completed'=>'primary','cancelled'=>'danger']; @endphp
                                    <span class="badge bg-{{ $stC[$detailProject->status] ?? 'secondary' }}">{{ $detailProject->status_label }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Customer</div>
                                    <div class="fw-semibold small">{{ $detailProject->customer?->name ?? '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs mb-3">
                        <li class="nav-item">
                            <button class="nav-link {{ $detailTab === 'overview' ? 'active' : '' }}" wire:click="$set('detailTab', 'overview')">
                                <i class="ri-dashboard-line me-1"></i> Overview
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $detailTab === 'costs' ? 'active' : '' }}" wire:click="$set('detailTab', 'costs')">
                                <i class="ri-money-dollar-circle-line me-1"></i> Biaya ({{ count($costItems) }})
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link {{ $detailTab === 'revenues' ? 'active' : '' }}" wire:click="$set('detailTab', 'revenues')">
                                <i class="ri-funds-line me-1"></i> Pendapatan ({{ count($revenueItems) }})
                            </button>
                        </li>
                    </ul>

                    {{-- Tab Content --}}
                    @if($detailTab === 'overview')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>Breakdown Biaya per Kategori</h6>
                            @if(!empty($summary['cost_by_category']))
                            <ul class="list-group list-group-flush">
                                @foreach($summary['cost_by_category'] as $cat => $total)
                                <li class="list-group-item d-flex justify-content-between px-0 py-2">
                                    <span>{{ \App\Models\Project::COST_CATEGORIES[$cat] ?? $cat }}</span>
                                    <strong>Rp {{ number_format($total, 0, ',', '.') }}</strong>
                                </li>
                                @endforeach
                            </ul>
                            @else
                            <p class="text-muted">Belum ada data biaya.</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h6>Info Proyek</h6>
                            <table class="table table-sm">
                                <tr><td class="text-muted">Kode</td><td class="fw-semibold">{{ $detailProject->project_code }}</td></tr>
                                <tr><td class="text-muted">Periode</td><td>{{ $detailProject->start_date->format('d/m/Y') }} — {{ $detailProject->end_date?->format('d/m/Y') ?? 'Belum ditentukan' }}</td></tr>
                                <tr><td class="text-muted">Deskripsi</td><td>{{ $detailProject->description ?? '-' }}</td></tr>
                                <tr><td class="text-muted">Catatan</td><td>{{ $detailProject->notes ?? '-' }}</td></tr>
                            </table>
                        </div>
                    </div>
                    @elseif($detailTab === 'costs')
                    <div class="d-flex justify-content-end mb-2">
                        @if(!in_array($detailProject->status, ['completed', 'cancelled']))
                        <button class="btn btn-primary btn-sm" wire:click="openCostForm">
                            <i class="ri-add-line"></i> Tambah Biaya
                        </button>
                        @endif
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="12%">Tanggal</th>
                                    <th width="15%">Kategori</th>
                                    <th>Keterangan</th>
                                    <th width="15%" class="text-end">Jumlah</th>
                                    <th width="8%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($costItems as $idx => $ci)
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td>{{ $ci['date'] }}</td>
                                    <td><span class="badge bg-info-subtle text-info">{{ $ci['category'] }}</span></td>
                                    <td>{{ $ci['description'] }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($ci['amount'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if(!in_array($detailProject->status, ['completed', 'cancelled']))
                                        <button class="btn btn-outline-danger btn-sm" wire:click="deleteCost({{ $ci['id'] }})"
                                            wire:confirm="Hapus item biaya ini?"><i class="ri-delete-bin-line"></i></button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data biaya.</td></tr>
                                @endforelse
                            </tbody>
                            @if(count($costItems) > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold">Rp {{ number_format(array_sum(array_column($costItems, 'amount')), 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    @elseif($detailTab === 'revenues')
                    <div class="d-flex justify-content-end mb-2">
                        @if(!in_array($detailProject->status, ['completed', 'cancelled']))
                        <button class="btn btn-success btn-sm" wire:click="openRevenueForm">
                            <i class="ri-add-line"></i> Tambah Pendapatan
                        </button>
                        @endif
                    </div>
                    <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                        <table class="table table-bordered table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="12%">Tanggal</th>
                                    <th>Keterangan</th>
                                    <th width="15%" class="text-end">Jumlah</th>
                                    <th width="8%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($revenueItems as $idx => $ri)
                                <tr>
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td>{{ $ri['date'] }}</td>
                                    <td>{{ $ri['description'] }}</td>
                                    <td class="text-end fw-semibold text-success">Rp {{ number_format($ri['amount'], 0, ',', '.') }}</td>
                                    <td class="text-center">
                                        @if(!in_array($detailProject->status, ['completed', 'cancelled']))
                                        <button class="btn btn-outline-danger btn-sm" wire:click="deleteRevenue({{ $ri['id'] }})"
                                            wire:confirm="Hapus item pendapatan ini?"><i class="ri-delete-bin-line"></i></button>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center text-muted py-3">Belum ada data pendapatan.</td></tr>
                                @endforelse
                            </tbody>
                            @if(count($revenueItems) > 0)
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="3" class="text-end fw-bold">Total</td>
                                    <td class="text-end fw-bold text-success">Rp {{ number_format(array_sum(array_column($revenueItems, 'amount')), 0, ',', '.') }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    @if($detailProject->status === 'planning')
                    <button class="btn btn-success btn-sm me-auto" wire:click="changeStatus({{ $detailProject->id }}, 'active')"
                        wire:confirm="Aktifkan proyek ini?"><i class="ri-play-line"></i> Aktifkan</button>
                    @elseif($detailProject->status === 'active')
                    <button class="btn btn-primary btn-sm me-auto" wire:click="changeStatus({{ $detailProject->id }}, 'completed')"
                        wire:confirm="Selesaikan proyek ini?"><i class="ri-check-double-line"></i> Selesaikan</button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeDetail">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ ADD COST MODAL ═══════════════ --}}
    @if($showCostForm)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); overflow-y: auto; z-index: 1060;">
        <div class="modal-dialog modal-md" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning py-2">
                    <h6 class="modal-title"><i class="ri-add-line me-1"></i> Tambah Biaya</h6>
                    <button type="button" class="btn-close btn-sm" wire:click="closeCostForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('item_date') is-invalid @enderror" wire:model="item_date">
                            @error('item_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select @error('item_category') is-invalid @enderror" wire:model="item_category">
                                @foreach(\App\Models\Project::COST_CATEGORIES as $k => $v)
                                <option value="{{ $k }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('item_category') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_description') is-invalid @enderror"
                                wire:model="item_description" placeholder="Deskripsi biaya">
                            @error('item_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('item_amount') is-invalid @enderror"
                                    wire:model="item_amount" step="0.01">
                            </div>
                            @error('item_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Catatan</label>
                            <input type="text" class="form-control" wire:model="item_notes" placeholder="Opsional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeCostForm">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="saveCost" wire:loading.attr="disabled">
                        <i class="ri-check-line"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ ADD REVENUE MODAL ═══════════════ --}}
    @if($showRevenueForm)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); overflow-y: auto; z-index: 1060;">
        <div class="modal-dialog modal-md" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title"><i class="ri-add-line me-1"></i> Tambah Pendapatan</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeRevenueForm"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('item_date') is-invalid @enderror" wire:model="item_date">
                            @error('item_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" class="form-control @error('item_amount') is-invalid @enderror"
                                    wire:model="item_amount" step="0.01">
                            </div>
                            @error('item_amount') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Keterangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_description') is-invalid @enderror"
                                wire:model="item_description" placeholder="Deskripsi pendapatan">
                            @error('item_description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label">Catatan</label>
                            <input type="text" class="form-control" wire:model="item_notes" placeholder="Opsional">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeRevenueForm">Batal</button>
                    <button type="button" class="btn btn-success btn-sm" wire:click="saveRevenue" wire:loading.attr="disabled">
                        <i class="ri-check-line"></i> Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
