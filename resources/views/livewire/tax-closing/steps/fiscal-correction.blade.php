{{-- Step 1: Koreksi Fiskal --}}
<div class="p-3">
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">
            <i class="ri-exchange-line text-warning me-2"></i>
            Koreksi Fiskal — {{ $selectedYear }}
        </h6>
        <button type="button" class="btn btn-primary btn-sm" wire:click="openModal">
            <i class="ri-add-line"></i> Tambah Koreksi
        </button>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-2 mb-3">
        <div class="col-md-4">
            <div class="alert alert-info py-2 mb-0">
                <small>
                    <i class="ri-list-check me-1"></i>
                    <strong>Total Item:</strong> {{ $correctionSummary['count'] }}
                </small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-success py-2 mb-0">
                <small>
                    <i class="ri-arrow-up-circle-line me-1"></i>
                    <strong>Koreksi Positif:</strong> Rp {{ number_format($correctionSummary['total_positive'], 0, ',', '.') }}
                </small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="alert alert-danger py-2 mb-0">
                <small>
                    <i class="ri-arrow-down-circle-line me-1"></i>
                    <strong>Koreksi Negatif:</strong> Rp {{ number_format($correctionSummary['total_negative'], 0, ',', '.') }}
                </small>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr class="table-dark">
                    <th width="5%">#</th>
                    <th width="30%">Deskripsi</th>
                    <th width="15%">Jenis Koreksi</th>
                    <th width="15%">Kategori</th>
                    <th width="15%" class="text-end">Jumlah</th>
                    <th width="10%">Catatan</th>
                    <th width="10%" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($corrections as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->description }}</td>
                    <td>
                        @if($item->correction_type === 'positive')
                            <span class="badge bg-success-subtle text-success">Positif (+)</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Negatif (-)</span>
                        @endif
                    </td>
                    <td>
                        @if($item->category === 'beda_tetap')
                            <span class="badge bg-primary-subtle text-primary">Beda Tetap</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Beda Waktu</span>
                        @endif
                    </td>
                    <td class="text-end fw-medium">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    <td><small class="text-muted">{{ $item->notes ?? '-' }}</small></td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-outline-warning" wire:click="editFiscalCorrection({{ $item->id }})"
                            title="Edit">
                            <i class="ri-pencil-line"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger"
                            onclick="confirmDeleteFiscalCorrection({{ $item->id }}, '{{ addslashes($item->description) }}')"
                            title="Hapus">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        <i class="ri-file-list-line fs-3 d-block mb-2"></i>
                        Tidak ada koreksi fiskal untuk tahun {{ $selectedYear }}
                    </td>
                </tr>
                @endforelse
            </tbody>
            @if($corrections->count() > 0)
            <tfoot class="table-light">
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Total Koreksi Positif:</td>
                    <td class="text-end text-success">Rp {{ number_format($correctionSummary['total_positive'], 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Total Koreksi Negatif:</td>
                    <td class="text-end text-danger">Rp {{ number_format($correctionSummary['total_negative'], 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr class="fw-bold table-secondary">
                    <td colspan="4" class="text-end">Koreksi Neto:</td>
                    <td class="text-end">Rp {{ number_format($correctionSummary['total_positive'] - $correctionSummary['total_negative'], 0, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            @endif
        </table>
    </div>
</div>

{{-- Modal Form --}}
@if($showModal)
<div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                    {{ $isEditing ? 'Edit' : 'Tambah' }} Koreksi Fiskal
                </h5>
                <button type="button" class="btn-close" wire:click="closeModal"></button>
            </div>
            <div class="modal-body">
                <form wire:submit="saveFiscalCorrection">
                    <div class="mb-3">
                        <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('description') is-invalid @enderror"
                            wire:model="description" placeholder="Keterangan koreksi fiskal">
                        @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Koreksi <span class="text-danger">*</span></label>
                            <select class="form-select @error('correction_type') is-invalid @enderror"
                                wire:model="correction_type">
                                <option value="positive">Positif (+)</option>
                                <option value="negative">Negatif (-)</option>
                            </select>
                            @error('correction_type')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori <span class="text-danger">*</span></label>
                            <select class="form-select @error('category') is-invalid @enderror" wire:model="category">
                                <option value="beda_tetap">Beda Tetap</option>
                                <option value="beda_waktu">Beda Waktu</option>
                            </select>
                            @error('category')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control @error('amount') is-invalid @enderror"
                            wire:model="amount" min="1" placeholder="0">
                        @error('amount')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control @error('notes') is-invalid @enderror" wire:model="notes"
                            rows="2" placeholder="Catatan opsional"></textarea>
                        @error('notes')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-light" wire:click="closeModal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> {{ $isEditing ? 'Perbarui' : 'Simpan' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@script
<script>
    window.confirmDeleteFiscalCorrection = function(id, description) {
        Swal.fire({
            title: 'Hapus Koreksi Fiskal?',
            text: `Apakah Anda yakin ingin menghapus "${description}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $wire.dispatch('deleteFiscalCorrection', [id]);
            }
        });
    };
</script>
@endscript
