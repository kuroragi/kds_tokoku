<!-- Journal Detail Modal -->
<div>
    @if ($showModal && $journal)
    <div class="modal fade show" style="display: block;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri-book-open-line"></i>
                        Detail Journal - {{ $journal->journal_no }}
                    </h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>

                <div class="modal-body">
                    <!-- Journal Header Info -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-3">
                                        <i class="ri-information-line"></i> Informasi Journal
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">No. Journal</small>
                                            <strong>{{ $journal->journal_no }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Tanggal</small>
                                            <strong>{{ $journal->journal_date->format('d M Y') }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Referensi</small>
                                            <strong>{{ $journal->reference ?? '-' }}</strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Periode</small>
                                            <strong>{{ $journal->period->period_name ?? '-' }}</strong>
                                        </div>
                                    </div>
                                    @if($journal->description)
                                    <div class="mt-3">
                                        <small class="text-muted d-block mb-1">Keterangan</small>
                                        <span>{{ $journal->description }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-3">
                                        <i class="ri-calculator-line"></i> Ringkasan Keuangan
                                    </h6>
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Total Debit</small>
                                            <strong class="text-primary">
                                                Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Total Credit</small>
                                            <strong class="text-danger">
                                                Rp {{ number_format($journal->total_credit, 0, ',', '.') }}
                                            </strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Status</small>
                                            @if($journal->status === 'draft')
                                            <span class="badge bg-warning">
                                                <i class="ri-draft-line"></i> Draft
                                            </span>
                                            @elseif($journal->status === 'posted')
                                            <span class="badge bg-success">
                                                <i class="ri-check-line"></i> Posted
                                            </span>
                                            @else
                                            <span class="badge bg-danger">
                                                <i class="ri-close-line"></i> Dibatalkan
                                            </span>
                                            @endif
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block mb-1">Keseimbangan</small>
                                            @if($journal->is_balanced)
                                            <span class="badge bg-success">
                                                <i class="ri-check-line"></i> Seimbang
                                            </span>
                                            @else
                                            <span class="badge bg-danger">
                                                <i class="ri-close-line"></i> Tidak Seimbang
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                    @if($journal->posted_at)
                                    <div class="mt-3">
                                        <small class="text-muted d-block mb-1">Diposting Pada</small>
                                        <span>{{ $journal->posted_at->format('d M Y H:i') }}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Journal Details Table -->
                    <div class="card border-0">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0">
                                <i class="ri-list-check-2"></i> Detail Journal Entry
                            </h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th width="5%" class="text-center">#</th>
                                            <th width="25%">Akun</th>
                                            <th width="30%">Keterangan</th>
                                            <th width="20%" class="text-end">Debit</th>
                                            <th width="20%" class="text-end">Credit</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($journal->journals->sortBy('sequence') as $detail)
                                        <tr>
                                            <td class="text-center">{{ $detail->sequence }}</td>
                                            <td>
                                                <div>
                                                    <strong class="text-primary">{{ $detail->coa->code }}</strong>
                                                    <div class="small text-muted">{{ $detail->coa->name }}</div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="text-muted">{{ $detail->description ?? '-' }}</span>
                                            </td>
                                            <td class="text-end">
                                                @if($detail->debit > 0)
                                                <strong class="text-primary">
                                                    Rp {{ number_format($detail->debit, 0, ',', '.') }}
                                                </strong>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                @if($detail->credit > 0)
                                                <strong class="text-danger">
                                                    Rp {{ number_format($detail->credit, 0, ',', '.') }}
                                                </strong>
                                                @else
                                                <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="table-dark">
                                        <tr>
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th class="text-end">
                                                Rp {{ number_format($journal->total_debit, 0, ',', '.') }}
                                            </th>
                                            <th class="text-end">
                                                Rp {{ number_format($journal->total_credit, 0, ',', '.') }}
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Audit Trail -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body p-3">
                                    <h6 class="card-title mb-3">
                                        <i class="ri-time-line"></i> Jejak Audit
                                    </h6>
                                    <div class="row small">
                                        <div class="col-md-4">
                                            <div class="border-end pe-3">
                                                <span class="text-muted d-block mb-1">Dibuat:</span>
                                                <strong>{{ $journal->created_at->format('d M Y H:i') }}</strong><br>
                                                <span class="text-muted">oleh {{ $journal->created_by_name ?? 'Sistem'
                                                    }}</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="border-end pe-3">
                                                <span class="text-muted d-block mb-1">Diperbarui:</span>
                                                <strong>{{ $journal->updated_at->format('d M Y H:i') }}</strong><br>
                                                <span class="text-muted">oleh {{ $journal->updated_by_name ?? 'Sistem'
                                                    }}</span>
                                            </div>
                                        </div>
                                        @if($journal->posted_at)
                                        <div class="col-md-4">
                                            <span class="text-muted d-block mb-1">Diposting:</span>
                                            <strong>{{ $journal->posted_at->format('d M Y H:i') }}</strong><br>
                                            <span class="text-muted">oleh {{ $journal->posted_by_name ?? 'Sistem'
                                                }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeModal">
                        <i class="ri-close-line"></i> Tutup
                    </button>

                    @if($journal->status === 'draft')
                    <button type="button" class="btn btn-primary"
                        wire:click="$dispatch('editJournal', { journalId: {{ $journal->id }} })"
                        onclick="document.querySelector('[wire\\:click*=closeModal]').click()">
                        <i class="ri-edit-line"></i> Edit Journal
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Backdrop -->
    <div class="modal-backdrop fade show"></div>
    @endif
</div>