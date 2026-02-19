<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-info text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-clipboard-line me-1"></i> Stock Opname Baru
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

                        {{-- Stock Items --}}
                        @if(count($details) > 0)
                        <h6 class="border-bottom pb-2 mb-3">
                            <i class="ri-store-line me-1"></i> Daftar Barang
                            <span class="badge bg-secondary ms-1">{{ count($details) }} item</span>
                        </h6>

                        <div class="table-responsive" style="max-height: 400px;">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th width="5%">#</th>
                                        <th width="15%">Kode</th>
                                        <th width="30%">Nama Barang</th>
                                        <th width="10%">Satuan</th>
                                        <th width="12%" class="text-center">Stok Sistem</th>
                                        <th width="15%">Stok Aktual <span class="text-danger">*</span></th>
                                        <th width="13%" class="text-center">Selisih</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($details as $idx => $detail)
                                    <tr wire:key="detail-{{ $idx }}" class="{{ ($detail['difference'] ?? 0) != 0 ? 'table-warning' : '' }}">
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td class="small">{{ $detail['stock_code'] }}</td>
                                        <td>{{ $detail['stock_name'] }}</td>
                                        <td class="text-center small">{{ $detail['unit'] ?? '-' }}</td>
                                        <td class="text-center fw-semibold">{{ number_format($detail['system_qty'], 2) }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0"
                                                class="form-control form-control-sm @error('details.'.$idx.'.actual_qty') is-invalid @enderror"
                                                wire:model.live.debounce.300ms="details.{{ $idx }}.actual_qty">
                                        </td>
                                        <td class="text-center">
                                            @php $diff = ($detail['actual_qty'] ?? 0) - ($detail['system_qty'] ?? 0); @endphp
                                            @if($diff > 0)
                                            <span class="badge bg-success">+{{ number_format($diff, 2) }}</span>
                                            @elseif($diff < 0)
                                            <span class="badge bg-danger">{{ number_format($diff, 2) }}</span>
                                            @else
                                            <span class="badge bg-secondary">0</span>
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
                                            $surplus = collect($details)->filter(fn($d) => (($d['actual_qty'] ?? 0) - ($d['system_qty'] ?? 0)) > 0)->count();
                                            $deficit = collect($details)->filter(fn($d) => (($d['actual_qty'] ?? 0) - ($d['system_qty'] ?? 0)) < 0)->count();
                                            $match = collect($details)->filter(fn($d) => (($d['actual_qty'] ?? 0) - ($d['system_qty'] ?? 0)) == 0)->count();
                                        @endphp
                                        <div class="small d-flex justify-content-between">
                                            <span>Surplus:</span> <span class="text-success fw-bold">{{ $surplus }} item</span>
                                        </div>
                                        <div class="small d-flex justify-content-between">
                                            <span>Defisit:</span> <span class="text-danger fw-bold">{{ $deficit }} item</span>
                                        </div>
                                        <div class="small d-flex justify-content-between">
                                            <span>Sesuai:</span> <span class="text-muted fw-bold">{{ $match }} item</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="text-center text-muted py-4">
                            <i class="ri-store-line fs-3 d-block mb-1"></i>
                            Pilih unit usaha untuk memuat daftar barang.
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-info btn-sm" wire:loading.attr="disabled" {{ count($details) === 0 ? 'disabled' : '' }}>
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan Opname</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
