<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-success text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-calculator-line me-1"></i> Proses Penyusutan Aset
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                    {{-- Pilih Unit & Periode --}}
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                            @if($isSuperAdmin)
                            <select class="form-select" wire:model.live="business_unit_id">
                                <option value="">-- Pilih Unit --</option>
                                @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                @endforeach
                            </select>
                            @else
                            <input type="text" class="form-control" value="{{ $units->first()?->name }}" readonly>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Periode <span class="text-danger">*</span></label>
                            <select class="form-select" wire:model="period_id">
                                <option value="">-- Pilih Periode --</option>
                                @foreach($periods as $period)
                                <option value="{{ $period->id }}">{{ $period->period_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary" wire:click="previewDepreciation">
                                <i class="ri-search-line me-1"></i> Preview
                            </button>
                        </div>
                    </div>

                    {{-- Preview Table --}}
                    @if(!empty($preview))
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="4%">#</th>
                                    <th width="10%">Kode</th>
                                    <th width="20%">Nama Aset</th>
                                    <th width="10%">Metode</th>
                                    <th width="14%" class="text-end">Penyusutan</th>
                                    <th width="14%" class="text-end">Akumulasi</th>
                                    <th width="14%" class="text-end">Nilai Buku</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($preview as $idx => $item)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td><code>{{ $item['asset']['code'] }}</code></td>
                                    <td>{{ $item['asset']['name'] }}</td>
                                    <td>
                                        <span class="badge bg-{{ $item['asset']['depreciation_method'] === 'straight_line' ? 'primary' : 'info' }} bg-opacity-75">
                                            {{ $item['asset']['depreciation_method'] === 'straight_line' ? 'GL' : 'SM' }}
                                        </span>
                                    </td>
                                    <td class="text-end text-danger">Rp {{ number_format($item['depreciation_amount'], 0, ',', '.') }}</td>
                                    <td class="text-end text-warning">Rp {{ number_format($item['accumulated_depreciation'], 0, ',', '.') }}</td>
                                    <td class="text-end fw-medium">Rp {{ number_format($item['book_value'], 0, ',', '.') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <td colspan="4" class="fw-bold text-end">Total Penyusutan:</td>
                                    <td class="text-end fw-bold text-danger">Rp {{ number_format($totalAmount, 0, ',', '.') }}</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="alert alert-warning mt-3 py-2 small">
                        <i class="ri-information-line me-1"></i>
                        Proses ini akan membuat {{ count($preview) }} catatan penyusutan dan 1 jurnal penyesuaian gabungan
                        (Debit Beban Penyusutan, Kredit Akumulasi Penyusutan) senilai Rp {{ number_format($totalAmount, 0, ',', '.') }}.
                    </div>
                    @endif
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="closeModal">Batal</button>
                    @if(!empty($preview))
                    <button type="button" class="btn btn-success btn-sm" wire:click="processDepreciation"
                        wire:confirm="Yakin ingin memproses penyusutan? Tindakan ini tidak bisa dibatalkan.">
                        <i class="ri-check-line me-1"></i> Proses Penyusutan
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
