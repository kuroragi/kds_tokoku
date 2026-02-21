<div>
    {{-- Header & Filters --}}
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Cari</label>
                    <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="search"
                        placeholder="Deskripsi, referensi...">
                </div>
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
                <div class="col-lg-2">
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
                        @foreach(\App\Models\BankMutation::STATUSES as $val => $label)
                        <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2">
                    <label class="form-label small text-muted mb-1">Batch</label>
                    <select class="form-select form-select-sm" wire:model.live="filterBatch">
                        <option value="">Semua</option>
                        @foreach($batches as $batch)
                        <option value="{{ $batch }}">{{ $batch }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-lg-2 text-end">
                    <button class="btn btn-primary btn-sm" wire:click="openImport">
                        <i class="ri-upload-2-line"></i> Import Mutasi
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
                        <th width="10%" style="cursor:pointer" wire:click="sortBy('transaction_date')">
                            Tanggal
                            @if($sortField === 'transaction_date')
                            <i class="ri-arrow-{{ $sortDirection === 'asc' ? 'up' : 'down' }}-s-line"></i>
                            @endif
                        </th>
                        <th width="12%">Rekening</th>
                        <th width="28%">Keterangan</th>
                        <th width="10%">Referensi</th>
                        <th width="10%" class="text-end">Debit</th>
                        <th width="10%" class="text-end">Kredit</th>
                        <th width="10%" class="text-end">Saldo</th>
                        <th width="6%" class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mutations as $idx => $m)
                    <tr wire:key="mut-{{ $m->id }}">
                        <td class="text-muted ps-3">{{ $mutations->firstItem() + $idx }}</td>
                        <td>{{ $m->transaction_date->format('d/m/Y') }}</td>
                        <td><small>{{ $m->bankAccount?->bank?->name }} {{ $m->bankAccount?->account_number }}</small></td>
                        <td>{{ Str::limit($m->description, 60) }}</td>
                        <td><small class="text-muted">{{ $m->reference_no ?? '-' }}</small></td>
                        <td class="text-end {{ $m->debit > 0 ? 'text-success fw-semibold' : '' }}">
                            {{ $m->debit > 0 ? 'Rp ' . number_format($m->debit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end {{ $m->credit > 0 ? 'text-danger fw-semibold' : '' }}">
                            {{ $m->credit > 0 ? 'Rp ' . number_format($m->credit, 0, ',', '.') : '-' }}
                        </td>
                        <td class="text-end">Rp {{ number_format($m->balance, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @php
                                $statusColors = ['unmatched' => 'warning', 'matched' => 'success', 'ignored' => 'secondary'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$m->status] ?? 'secondary' }}">
                                {{ $m->status_label }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="ri-file-list-3-line fs-3 d-block mb-2"></i>
                            Belum ada mutasi bank. Klik "Import Mutasi" untuk memulai.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($mutations->hasPages())
        <div class="card-footer bg-white border-0 py-2">
            {{ $mutations->links() }}
        </div>
        @endif
    </div>

    {{-- ═══════════════ IMPORT MODAL ═══════════════ --}}
    @if($showImportModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-lg" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title"><i class="ri-upload-2-line me-1"></i> Import Mutasi Bank</h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeImport"></button>
                </div>
                <div class="modal-body">
                    @if($importResult)
                    <div class="alert alert-success py-2">
                        <i class="ri-check-line"></i>
                        Import selesai: <strong>{{ $importResult['imported'] }}</strong> berhasil,
                        <strong>{{ $importResult['skipped'] }}</strong> dilewati.
                        Batch: <code>{{ $importResult['batch'] }}</code>
                    </div>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Rekening Bank <span class="text-danger">*</span></label>
                            <select class="form-select @error('import_bank_account_id') is-invalid @enderror" wire:model="import_bank_account_id">
                                <option value="">-- Pilih Rekening --</option>
                                @foreach($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->name }} - {{ $acc->account_number }} ({{ $acc->account_name }})</option>
                                @endforeach
                            </select>
                            @error('import_bank_account_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Preset Bank</label>
                            <select class="form-select" wire:model.live="import_preset">
                                @foreach($presets as $key => $preset)
                                <option value="{{ $key }}">{{ $preset['label'] }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Atur mapping kolom otomatis sesuai bank.</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label">File CSV/Excel <span class="text-danger">*</span></label>
                            <input type="file" class="form-control @error('importFile') is-invalid @enderror"
                                wire:model="importFile" accept=".csv,.xlsx,.xls">
                            @error('importFile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">Format: CSV, XLSX, XLS. (Maks 5MB). Baris pertama harus berisi header kolom.</small>
                        </div>
                    </div>

                    {{-- Column Mapping --}}
                    <h6 class="mt-4 mb-3 border-bottom pb-2"><i class="ri-settings-3-line me-1"></i> Mapping Kolom</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small">Kolom Tanggal <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" wire:model="col_date" placeholder="tanggal">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Kolom Keterangan <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" wire:model="col_description" placeholder="keterangan">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Format Tanggal</label>
                            <input type="text" class="form-control form-control-sm" wire:model="import_date_format" placeholder="d/m/Y">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kolom Debit</label>
                            <input type="text" class="form-control form-control-sm" wire:model="col_debit" placeholder="debet">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kolom Kredit</label>
                            <input type="text" class="form-control form-control-sm" wire:model="col_credit" placeholder="kredit">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kolom Saldo</label>
                            <input type="text" class="form-control form-control-sm" wire:model="col_balance" placeholder="saldo">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">Kolom Referensi</label>
                            <input type="text" class="form-control form-control-sm" wire:model="col_reference" placeholder="referensi (opsional)">
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeImport">Tutup</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="importMutations" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="importMutations"><i class="ri-upload-2-line"></i> Import</span>
                        <span wire:loading wire:target="importMutations"><i class="ri-loader-4-line"></i> Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
