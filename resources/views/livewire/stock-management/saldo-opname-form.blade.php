<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-wallet-line me-1"></i> Saldo Opname Baru
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        {{-- Header --}}
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror" wire:model.live="business_unit_id">
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->firstWhere('id', $business_unit_id)?->name ?? '-' }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tanggal Opname <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('opname_date') is-invalid @enderror"
                                    wire:model="opname_date">
                                @error('opname_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-5">
                                <label class="form-label">Catatan</label>
                                <input type="text" class="form-control" wire:model="notes" placeholder="Catatan opname (opsional)">
                            </div>
                        </div>

                        {{-- Provider Items --}}
                        @if(count($details) > 0)
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-bank-card-line me-1"></i> Daftar Provider / Saldo
                            <span class="badge bg-secondary ms-1">{{ count($details) }} provider</span>
                        </h6>

                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="30%">Nama Provider</th>
                                        <th width="12%">Tipe</th>
                                        <th width="18%" class="text-end">Saldo Sistem</th>
                                        <th width="18%">Saldo Aktual <span class="text-danger">*</span></th>
                                        <th width="17%" class="text-end">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($details as $idx => $detail)
                                    <tr wire:key="detail-{{ $idx }}" class="{{ ($detail['difference'] ?? 0) != 0 ? 'table-warning' : '' }}">
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>{{ $detail['provider_name'] }}</td>
                                        <td>
                                            <span class="badge {{ ($detail['provider_type'] ?? '') === 'bank' ? 'bg-primary' : 'bg-secondary' }}">
                                                {{ ucfirst($detail['provider_type'] ?? '-') }}
                                            </span>
                                        </td>
                                        <td class="text-end fw-semibold">Rp {{ number_format($detail['system_balance'], 0, ',', '.') }}</td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">Rp</span>
                                                <input type="number" step="0.01"
                                                    class="form-control @error('details.'.$idx.'.actual_balance') is-invalid @enderror"
                                                    wire:model.live.debounce.300ms="details.{{ $idx }}.actual_balance">
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @php $diff = ($detail['actual_balance'] ?? 0) - ($detail['system_balance'] ?? 0); @endphp
                                            @if($diff > 0)
                                            <span class="text-success fw-bold">+Rp {{ number_format($diff, 0, ',', '.') }}</span>
                                            @elseif($diff < 0)
                                            <span class="text-danger fw-bold">-Rp {{ number_format(abs($diff), 0, ',', '.') }}</span>
                                            @else
                                            <span class="text-muted">Rp 0</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Summary --}}
                        <div class="row mt-3">
                            <div class="col-md-4 offset-md-8">
                                <div class="card bg-light border-0">
                                    <div class="card-body p-2">
                                        @php
                                            $surplus = collect($details)->filter(fn($d) => (($d['actual_balance'] ?? 0) - ($d['system_balance'] ?? 0)) > 0)->count();
                                            $deficit = collect($details)->filter(fn($d) => (($d['actual_balance'] ?? 0) - ($d['system_balance'] ?? 0)) < 0)->count();
                                            $match = collect($details)->filter(fn($d) => (($d['actual_balance'] ?? 0) - ($d['system_balance'] ?? 0)) == 0)->count();
                                            $totalDiff = collect($details)->sum(fn($d) => ($d['actual_balance'] ?? 0) - ($d['system_balance'] ?? 0));
                                        @endphp
                                        <div class="small d-flex justify-content-between">
                                            <span>Surplus:</span> <span class="text-success fw-bold">{{ $surplus }} provider</span>
                                        </div>
                                        <div class="small d-flex justify-content-between">
                                            <span>Defisit:</span> <span class="text-danger fw-bold">{{ $deficit }} provider</span>
                                        </div>
                                        <div class="small d-flex justify-content-between">
                                            <span>Sesuai:</span> <span class="text-muted fw-bold">{{ $match }} provider</span>
                                        </div>
                                        <hr class="my-1">
                                        <div class="small d-flex justify-content-between">
                                            <span class="fw-bold">Total Selisih:</span>
                                            <span class="fw-bold {{ $totalDiff >= 0 ? 'text-success' : 'text-danger' }}">
                                                {{ $totalDiff >= 0 ? '+' : '-' }}Rp {{ number_format(abs($totalDiff), 0, ',', '.') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center text-muted py-4">
                            <i class="ri-wallet-line fs-3 d-block mb-1"></i>
                            Pilih unit usaha untuk memuat daftar provider.
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-info btn-sm" wire:loading.attr="disabled" {{ count($details) === 0 ? 'disabled' : '' }}>
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan Opname Saldo</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
