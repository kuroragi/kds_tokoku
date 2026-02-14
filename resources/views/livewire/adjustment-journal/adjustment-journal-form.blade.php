<!-- Adjustment Journal Form Modal -->
<div>
    @if ($showModal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info bg-opacity-10">
                    <h5 class="modal-title">
                        <i class="ri-file-edit-line text-info"></i>
                        {{ $isEditing ? 'Edit Jurnal Penyesuaian' : 'Tambah Jurnal Penyesuaian Baru' }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                <form wire:submit="save">
                    <div class="modal-body">
                        <!-- Journal Header -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">
                                    <i class="ri-information-line"></i> Informasi Jurnal Penyesuaian
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 mb-3">
                                        <label for="adj_journal_no" class="form-label">
                                            Nomor Jurnal <span class="text-danger">*</span>
                                        </label>
                                        <input type="text" class="form-control" id="adj_journal_no"
                                            wire:model="journal_no" readonly>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="adj_journal_date" class="form-label">
                                            Tanggal Jurnal <span class="text-danger">*</span>
                                        </label>
                                        <input type="date"
                                            class="form-control @error('journal_date') is-invalid @enderror"
                                            id="adj_journal_date" wire:model.change="journal_date" required>
                                        @error('journal_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="adj_id_period" class="form-label">
                                            Periode <span class="text-danger">*</span>
                                        </label>
                                        <select class="form-select @error('id_period') is-invalid @enderror"
                                            id="adj_id_period" wire:model="id_period" required disabled>
                                            <option value="">-- Pilih tanggal terlebih dahulu --</option>
                                            @foreach($periods as $period)
                                            <option value="{{ $period->id }}">
                                                {{ $period->period_name }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('id_period')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="adj_reference" class="form-label">Referensi</label>
                                        <input type="text"
                                            class="form-control @error('reference') is-invalid @enderror"
                                            id="adj_reference" wire:model="reference" placeholder="Nomor referensi">
                                        @error('reference')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <label for="adj_description" class="form-label">Keterangan</label>
                                        <textarea class="form-control @error('description') is-invalid @enderror"
                                            id="adj_description" wire:model="description" rows="2"
                                            placeholder="Keterangan penyesuaian (misal: penyesuaian beban sewa, penyusutan, dll)"></textarea>
                                        @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Journal Details -->
                        <div class="card mb-3">
                            <div class="card-header bg-light">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0">
                                        <i class="ri-list-check-2"></i> Detail Entri Penyesuaian
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                        wire:click="addJournalRow">
                                        <i class="ri-add-line"></i> Tambah Baris
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="30%">Akun</th>
                                                <th width="25%">Keterangan</th>
                                                <th width="15%" class="text-end">Debit</th>
                                                <th width="15%" class="text-end">Kredit</th>
                                                <th width="10%" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($journalDetails as $index => $detail)
                                            <tr wire:key="adj-detail-{{ $index }}">
                                                <td>
                                                    <select
                                                        class="form-select form-select-sm @error('journalDetails.'.$index.'.id_coa') is-invalid @enderror"
                                                        wire:model="journalDetails.{{ $index }}.id_coa">
                                                        <option value="">-- Pilih akun --</option>
                                                        @foreach($coas as $coa)
                                                        <option value="{{ $coa->id }}">
                                                            {{ $coa->code }} - {{ $coa->name }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                    @error('journalDetails.'.$index.'.id_coa')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="text" class="form-control form-control-sm"
                                                        wire:model="journalDetails.{{ $index }}.description"
                                                        placeholder="Keterangan">
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        class="form-control form-control-sm text-end @error('journalDetails.'.$index.'.debit') is-invalid @enderror"
                                                        wire:model.blur="journalDetails.{{ $index }}.debit" min="0"
                                                        step="0.01" placeholder="0">
                                                    @error('journalDetails.'.$index.'.debit')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td>
                                                    <input type="number"
                                                        class="form-control form-control-sm text-end @error('journalDetails.'.$index.'.credit') is-invalid @enderror"
                                                        wire:model.blur="journalDetails.{{ $index }}.credit" min="0"
                                                        step="0.01" placeholder="0">
                                                    @error('journalDetails.'.$index.'.credit')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                    @enderror
                                                </td>
                                                <td class="text-center">
                                                    @if(count($journalDetails) > 2)
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                        wire:click="removeJournalRow({{ $index }})" title="Hapus Baris">
                                                        <i class="ri-delete-bin-line"></i>
                                                    </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <th colspan="2" class="text-end">Total:</th>
                                                <th class="text-end">
                                                    <strong class="text-primary">{{ number_format($totalDebit, 0, ',',
                                                        '.') }}</strong>
                                                </th>
                                                <th class="text-end">
                                                    <strong class="text-danger">{{ number_format($totalCredit, 0, ',',
                                                        '.') }}</strong>
                                                </th>
                                                <th class="text-center">
                                                    @if($totalDebit == $totalCredit && $totalDebit > 0)
                                                    <i class="ri-check-line text-success" title="Seimbang"></i>
                                                    @elseif($totalDebit > 0 || $totalCredit > 0)
                                                    <i class="ri-close-line text-danger" title="Tidak Seimbang"></i>
                                                    @endif
                                                </th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Balance Check -->
                        @if($totalDebit != $totalCredit && ($totalDebit > 0 || $totalCredit > 0))
                        <div class="alert alert-warning">
                            <i class="ri-alert-line"></i>
                            <strong>Peringatan:</strong> Jurnal tidak seimbang.
                            Selisih: <strong>{{ number_format(abs($totalDebit - $totalCredit), 0, ',', '.') }}</strong>
                        </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" {{ $totalDebit
                            !=$totalCredit ? 'disabled' : '' }}>
                            <span wire:loading.remove>
                                <i class="ri-save-line"></i>
                                {{ $isEditing ? 'Perbarui Jurnal Penyesuaian' : 'Simpan Jurnal Penyesuaian' }}
                            </span>
                            <span wire:loading>
                                <i class="ri-loader-4-line spin"></i> Menyimpan...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Backdrop -->
    <div class="modal-backdrop fade show"></div>
    @endif
</div>

@push('style')
<style>
    .spin {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from {
            transform: rotate(0deg);
        }

        to {
            transform: rotate(360deg);
        }
    }
</style>
@endpush
