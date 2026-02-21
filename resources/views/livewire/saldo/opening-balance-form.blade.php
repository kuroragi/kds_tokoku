<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5); overflow-y: auto;">
        <div class="modal-dialog modal-xl" style="margin: 1.75rem auto;">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-scales-3-line me-1"></i>
                        {{ $isEditing ? 'Edit Saldo Awal' : 'Buat Saldo Awal' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Header --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($this->isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model.live="business_unit_id" {{ $isEditing ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($this->units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $this->units->firstWhere('id', $business_unit_id)?->name ?? '-' }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Periode <span class="text-danger">*</span></label>
                                <select class="form-select @error('period_id') is-invalid @enderror" wire:model="period_id" {{ $isEditing ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Periode --</option>
                                    @foreach($this->periods as $period)
                                    <option value="{{ $period->id }}">{{ $period->name }} ({{ $period->code }})</option>
                                    @endforeach
                                </select>
                                @error('period_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Tanggal Saldo Awal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('balance_date') is-invalid @enderror" wire:model="balance_date">
                                @error('balance_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Keterangan</label>
                                <input type="text" class="form-control" wire:model="description" placeholder="Saldo Awal">
                            </div>
                        </div>

                        {{-- Summary Bar --}}
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <div class="card border-0 bg-light">
                                    <div class="card-body py-2 text-center">
                                        <div class="text-muted small">Total Debit</div>
                                        <div class="fw-bold text-primary fs-5">Rp {{ number_format($this->totalDebit, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 bg-light">
                                    <div class="card-body py-2 text-center">
                                        <div class="text-muted small">Total Credit</div>
                                        <div class="fw-bold text-primary fs-5">Rp {{ number_format($this->totalCredit, 0, ',', '.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 {{ $this->isBalanced ? 'bg-success bg-opacity-10' : 'bg-danger bg-opacity-10' }}">
                                    <div class="card-body py-2 text-center">
                                        <div class="text-muted small">Selisih</div>
                                        <div class="fw-bold fs-5 {{ $this->isBalanced ? 'text-success' : 'text-danger' }}">
                                            @if($this->isBalanced)
                                            <i class="ri-checkbox-circle-line"></i> Balance
                                            @else
                                            <i class="ri-error-warning-line"></i> Rp {{ number_format($this->difference, 0, ',', '.') }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- COA Entries --}}
                        @if(count($entries) > 0)
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-list-check me-1"></i> Entri Saldo Awal
                            <small class="text-muted ms-2">({{ count($entries) }} akun)</small>
                        </h6>

                        <div class="mb-3">
                            <input type="text" class="form-control form-control-sm" wire:model.live.debounce.300ms="coaSearch"
                                placeholder="Cari kode atau nama akun..." style="max-width: 300px;">
                        </div>

                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light position-sticky top-0">
                                    <tr>
                                        <th width="10%">Kode</th>
                                        <th width="30%">Nama Akun</th>
                                        <th width="8%">Tipe</th>
                                        <th width="5%" class="text-center">Map</th>
                                        <th width="18%">Debit</th>
                                        <th width="18%">Credit</th>
                                        <th width="11%">Catatan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entries as $idx => $entry)
                                    @php
                                        $show = true;
                                        if ($coaSearch) {
                                            $s = strtolower($coaSearch);
                                            $show = str_contains(strtolower($entry['coa_code']), $s) || str_contains(strtolower($entry['coa_name']), $s);
                                        }
                                    @endphp
                                    @if($show)
                                    <tr wire:key="entry-{{ $idx }}" class="{{ ($entry['debit'] ?? 0) > 0 || ($entry['credit'] ?? 0) > 0 ? 'table-info' : '' }}">
                                        <td><code class="text-dark">{{ $entry['coa_code'] }}</code></td>
                                        <td class="fw-semibold">{{ $entry['coa_name'] }}</td>
                                        <td>
                                            <span class="badge {{ match($entry['coa_type'] ?? '') {
                                                'asset' => 'bg-primary',
                                                'liability' => 'bg-warning text-dark',
                                                'equity' => 'bg-info',
                                                'revenue' => 'bg-success',
                                                'expense' => 'bg-danger',
                                                default => 'bg-secondary'
                                            } }}">
                                                {{ match($entry['coa_type'] ?? '') {
                                                    'asset' => 'Aset',
                                                    'liability' => 'Kewajiban',
                                                    'equity' => 'Modal',
                                                    'revenue' => 'Pendapatan',
                                                    'expense' => 'Beban',
                                                    default => $entry['coa_type'] ?? '-'
                                                } }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($entry['is_mapped'] ?? false)
                                            <i class="ri-checkbox-circle-fill text-success"></i>
                                            @else
                                            <i class="ri-checkbox-blank-circle-line text-muted"></i>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" wire:model.live.debounce.500ms="entries.{{ $idx }}.debit"
                                                    min="0" step="0.01" placeholder="0">
                                            </div>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" class="form-control" wire:model.live.debounce.500ms="entries.{{ $idx }}.credit"
                                                    min="0" step="0.01" placeholder="0">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" wire:model="entries.{{ $idx }}.notes"
                                                placeholder="..." style="font-size: 0.75rem;">
                                        </td>
                                    </tr>
                                    @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-info text-center py-4">
                            <i class="ri-information-line fs-4 me-1"></i>
                            Pilih unit usaha untuk menampilkan daftar akun (COA).
                        </div>
                        @endif
                    </div>

                    <div class="modal-footer bg-light py-2">
                        <div class="me-auto">
                            @if(!$this->isBalanced && count($entries) > 0)
                            <small class="text-danger"><i class="ri-error-warning-line"></i> Total debit dan credit belum balance.</small>
                            @endif
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan Saldo Awal</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
