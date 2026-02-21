<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                @if($isSuperAdmin)
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Unit Usaha</label>
                    <select class="form-select form-select-sm" wire:model.live="filterUnit">
                        <option value="">Semua</option>
                        @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-lg-3">
                    <label class="form-label small text-muted mb-1">Rekening</label>
                    <select class="form-select form-select-sm" wire:model.live="filterAccount">
                        <option value="">Semua</option>
                        @foreach($bankAccounts as $acc)
                        <option value="{{ $acc->id }}">{{ $acc->bank?->name }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Status</label>
                    <select class="form-select form-select-sm" wire:model.live="filterStatus">
                        <option value="">Semua</option>
                        <option value="draft">Draft</option>
                        <option value="completed">Selesai</option>
                    </select>
                </div>
                <div class="col-lg-5 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="openCreate">
                        <i class="ri-add-line"></i> Buat Rekonsiliasi
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
                        <th width="18%">Rekening</th>
                        <th width="12%">Periode</th>
                        <th width="12%" class="text-end">Saldo Rek. Koran</th>
                        <th width="12%" class="text-end">Saldo Sistem</th>
                        <th width="10%" class="text-end">Selisih</th>
                        <th width="10%">Progress</th>
                        <th width="8%" class="text-center">Status</th>
                        <th width="14%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($reconciliations as $idx => $r)
                    <tr wire:key="recon-{{ $r->id }}">
                        <td class="text-muted ps-3">{{ $idx + 1 }}</td>
                        <td>
                            <span class="fw-semibold">{{ $r->bankAccount?->bank?->name }}</span><br>
                            <small class="text-muted">{{ $r->bankAccount?->account_number }} — {{ $r->bankAccount?->account_name }}</small>
                        </td>
                        <td>
                            {{ $r->start_date->format('d/m/Y') }}<br>
                            <small class="text-muted">s/d {{ $r->end_date->format('d/m/Y') }}</small>
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($r->bank_statement_balance, 0, ',', '.') }}</td>
                        <td class="text-end">Rp {{ number_format($r->system_balance, 0, ',', '.') }}</td>
                        <td class="text-end {{ abs($r->difference) > 0 ? 'text-danger fw-semibold' : 'text-success' }}">
                            Rp {{ number_format($r->difference, 0, ',', '.') }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-success" style="width: {{ $r->match_percentage }}%"></div>
                                </div>
                                <small class="text-muted">{{ $r->match_percentage }}%</small>
                            </div>
                            <small class="text-muted">{{ $r->matched_count }}/{{ $r->total_mutations }}</small>
                        </td>
                        <td class="text-center">
                            @if($r->status === 'completed')
                            <span class="badge bg-success"><i class="ri-check-line"></i> Selesai</span>
                            @else
                            <span class="badge bg-warning text-dark"><i class="ri-draft-line"></i> Draft</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" wire:click="openDetail({{ $r->id }})" title="Detail">
                                    <i class="ri-eye-line"></i>
                                </button>
                                @if($r->status === 'draft')
                                <button class="btn btn-outline-success" wire:click="completeRecon({{ $r->id }})"
                                    wire:confirm="Selesaikan rekonsiliasi ini?" title="Selesaikan">
                                    <i class="ri-check-double-line"></i>
                                </button>
                                <button class="btn btn-outline-danger" wire:click="deleteRecon({{ $r->id }})"
                                    wire:confirm="Hapus rekonsiliasi ini?" title="Hapus">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                                @else
                                <button class="btn btn-outline-warning" wire:click="reopenRecon({{ $r->id }})"
                                    wire:confirm="Buka kembali rekonsiliasi ini?" title="Buka Kembali">
                                    <i class="ri-arrow-go-back-line"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ri-file-search-line fs-3 d-block mb-2"></i>
                            Belum ada rekonsiliasi. Import mutasi bank terlebih dahulu.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════ CREATE MODAL ═══════════════ --}}
    @if($showCreateModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-md" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title"><i class="ri-file-add-line me-1"></i> Buat Rekonsiliasi Baru</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeCreate"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Rekening Bank <span class="text-danger">*</span></label>
                        <select class="form-select @error('create_bank_account_id') is-invalid @enderror" wire:model="create_bank_account_id">
                            <option value="">-- Pilih Rekening --</option>
                            @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank?->name }} - {{ $acc->account_number }} ({{ $acc->account_name }})</option>
                            @endforeach
                        </select>
                        @error('create_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('create_start_date') is-invalid @enderror" wire:model="create_start_date">
                            @error('create_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal Selesai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('create_end_date') is-invalid @enderror" wire:model="create_end_date">
                            @error('create_end_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Rekening Koran (Akhir Periode) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control @error('create_bank_statement_balance') is-invalid @enderror"
                                wire:model="create_bank_statement_balance" step="0.01">
                        </div>
                        @error('create_bank_statement_balance') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        <small class="text-muted">Masukkan saldo akhir sesuai rekening koran bank.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" wire:model="create_notes" rows="2" placeholder="Catatan rekonsiliasi (opsional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeCreate">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="createReconciliation" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createReconciliation"><i class="ri-check-line"></i> Buat & Proses</span>
                        <span wire:loading wire:target="createReconciliation"><i class="ri-loader-4-line"></i> Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ DETAIL MODAL ═══════════════ --}}
    @if($showDetailModal && $detailRecon)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-xl" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-file-search-line me-1"></i>
                        Rekonsiliasi: {{ $detailRecon->bankAccount?->bank?->name }} — {{ $detailRecon->bankAccount?->account_number }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeDetail"></button>
                </div>
                <div class="modal-body">
                    {{-- Summary --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Saldo Rek. Koran</div>
                                    <div class="fw-bold text-primary">Rp {{ number_format($detailRecon->bank_statement_balance, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Saldo Sistem</div>
                                    <div class="fw-bold">Rp {{ number_format($detailRecon->system_balance, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 {{ abs($detailRecon->difference) > 0 ? 'bg-danger bg-opacity-10' : 'bg-success bg-opacity-10' }}">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Selisih</div>
                                    <div class="fw-bold {{ abs($detailRecon->difference) > 0 ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($detailRecon->difference, 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2 text-center">
                                    <div class="text-muted small">Progress</div>
                                    <div class="fw-bold text-success">{{ $detailRecon->match_percentage }}%</div>
                                    <small class="text-muted">{{ $detailRecon->matched_count }}/{{ $detailRecon->total_mutations }} cocok</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Filter --}}
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-sm {{ $detailFilter === '' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('detailFilter', '')">Semua</button>
                        <button class="btn btn-sm {{ $detailFilter === 'unmatched' ? 'btn-warning' : 'btn-outline-warning' }}" wire:click="$set('detailFilter', 'unmatched')">Belum Cocok</button>
                        <button class="btn btn-sm {{ $detailFilter === 'matched' ? 'btn-success' : 'btn-outline-success' }}" wire:click="$set('detailFilter', 'matched')">Cocok</button>
                        <button class="btn btn-sm {{ $detailFilter === 'ignored' ? 'btn-secondary' : 'btn-outline-secondary' }}" wire:click="$set('detailFilter', 'ignored')">Diabaikan</button>
                    </div>

                    {{-- Items Table --}}
                    <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light position-sticky top-0">
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="10%">Tanggal</th>
                                    <th width="25%">Keterangan</th>
                                    <th width="8%">Referensi</th>
                                    <th width="10%" class="text-end">Debit</th>
                                    <th width="10%" class="text-end">Kredit</th>
                                    <th width="10%">Status</th>
                                    <th width="10%">Cocok Dgn</th>
                                    <th width="13%" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detailItems as $idx => $item)
                                <tr wire:key="detail-{{ $item['id'] }}" class="{{ $item['match_type'] === 'unmatched' ? '' : ($item['match_type'] === 'ignored' ? 'table-secondary' : 'table-success') }}">
                                    <td class="text-muted">{{ $idx + 1 }}</td>
                                    <td>{{ $item['mutation_date'] }}</td>
                                    <td>{{ Str::limit($item['mutation_desc'], 50) }}</td>
                                    <td><small>{{ $item['mutation_ref'] }}</small></td>
                                    <td class="text-end {{ $item['mutation_debit'] > 0 ? 'text-success' : '' }}">
                                        {{ $item['mutation_debit'] > 0 ? 'Rp ' . number_format($item['mutation_debit'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td class="text-end {{ $item['mutation_credit'] > 0 ? 'text-danger' : '' }}">
                                        {{ $item['mutation_credit'] > 0 ? 'Rp ' . number_format($item['mutation_credit'], 0, ',', '.') : '-' }}
                                    </td>
                                    <td>
                                        @php
                                            $mtColors = ['auto_matched' => 'success', 'manual_matched' => 'info', 'unmatched' => 'warning', 'ignored' => 'secondary', 'adjustment' => 'primary'];
                                        @endphp
                                        <span class="badge bg-{{ $mtColors[$item['match_type']] ?? 'secondary' }}">{{ $item['match_type_label'] }}</span>
                                    </td>
                                    <td><small>{{ $item['matched_ref'] }}</small></td>
                                    <td class="text-center">
                                        @if($detailRecon->status === 'draft')
                                        <div class="btn-group btn-group-sm">
                                            @if($item['match_type'] === 'unmatched')
                                            <button class="btn btn-outline-primary" wire:click="openMatchModal({{ $item['id'] }})" title="Cocokkan Manual">
                                                <i class="ri-link"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary" wire:click="ignoreItem({{ $item['id'] }})"
                                                wire:confirm="Abaikan mutasi ini?" title="Abaikan">
                                                <i class="ri-eye-off-line"></i>
                                            </button>
                                            @elseif(in_array($item['match_type'], ['auto_matched', 'manual_matched']))
                                            <button class="btn btn-outline-warning" wire:click="unmatchItem({{ $item['id'] }})"
                                                wire:confirm="Batalkan pencocokan?" title="Batalkan">
                                                <i class="ri-link-unlink"></i>
                                            </button>
                                            @elseif($item['match_type'] === 'ignored')
                                            <button class="btn btn-outline-warning" wire:click="unmatchItem({{ $item['id'] }})"
                                                title="Kembalikan">
                                                <i class="ri-arrow-go-back-line"></i>
                                            </button>
                                            @endif
                                        </div>
                                        @else
                                        <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-3">Tidak ada item.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    @if($detailRecon->status === 'draft')
                    <button class="btn btn-success btn-sm me-auto" wire:click="completeRecon({{ $detailRecon->id }})"
                        wire:confirm="Selesaikan rekonsiliasi ini?">
                        <i class="ri-check-double-line"></i> Selesaikan
                    </button>
                    @endif
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeDetail">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════════ MANUAL MATCH MODAL ═══════════════ --}}
    @if($showMatchModal && $matchItemInfo)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6); overflow-y: auto; z-index: 1060;">
        <div class="modal-dialog modal-lg" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-warning py-2">
                    <h6 class="modal-title"><i class="ri-link me-1"></i> Cocokkan Manual</h6>
                    <button type="button" class="btn-close btn-sm" wire:click="closeMatchModal"></button>
                </div>
                <div class="modal-body">
                    {{-- Mutation Info --}}
                    <div class="alert alert-info py-2 mb-3">
                        <div class="row">
                            <div class="col-3"><small class="text-muted">Tanggal</small><div class="fw-semibold">{{ $matchItemInfo['date'] }}</div></div>
                            <div class="col-5"><small class="text-muted">Keterangan</small><div class="fw-semibold">{{ Str::limit($matchItemInfo['description'], 50) }}</div></div>
                            <div class="col-2"><small class="text-muted">Debit</small><div class="fw-semibold text-success">{{ $matchItemInfo['debit'] > 0 ? 'Rp '.number_format($matchItemInfo['debit'],0,',','.') : '-' }}</div></div>
                            <div class="col-2"><small class="text-muted">Kredit</small><div class="fw-semibold text-danger">{{ $matchItemInfo['credit'] > 0 ? 'Rp '.number_format($matchItemInfo['credit'],0,',','.') : '-' }}</div></div>
                        </div>
                    </div>

                    {{-- Search --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <select class="form-select form-select-sm" wire:model.live="matchType">
                                <option value="journal">Jurnal</option>
                                <option value="fund_transfer">Transfer Dana</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control form-control-sm" wire:model="matchSearchQuery"
                                placeholder="Cari no jurnal / referensi / deskripsi...">
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary btn-sm w-100" wire:click="searchMatch">
                                <i class="ri-search-line"></i> Cari
                            </button>
                        </div>
                    </div>

                    {{-- Results --}}
                    @if(count($matchResults) > 0)
                    <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%"></th>
                                    <th>Referensi</th>
                                    <th>Tanggal</th>
                                    <th>Keterangan</th>
                                    <th class="text-end">Jumlah</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($matchResults as $mr)
                                <tr wire:key="match-{{ $mr['id'] }}" class="{{ $selectedMatchId == $mr['id'] ? 'table-primary' : '' }}"
                                    style="cursor: pointer" wire:click="$set('selectedMatchId', {{ $mr['id'] }})">
                                    <td class="text-center">
                                        <input type="radio" name="matchSelect" value="{{ $mr['id'] }}"
                                            {{ $selectedMatchId == $mr['id'] ? 'checked' : '' }}>
                                    </td>
                                    <td class="fw-semibold">{{ $mr['ref'] }}</td>
                                    <td>{{ $mr['date'] }}</td>
                                    <td>{{ Str::limit($mr['desc'], 40) }}</td>
                                    <td class="text-end fw-semibold">Rp {{ number_format($mr['amount'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($matchSearchQuery)
                    <div class="text-center text-muted py-3">
                        <i class="ri-search-line fs-4"></i>
                        <p class="mb-0">Tidak ditemukan.</p>
                    </div>
                    @endif
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeMatchModal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="confirmMatch"
                        {{ !$selectedMatchId ? 'disabled' : '' }}>
                        <i class="ri-check-line"></i> Cocokkan
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
