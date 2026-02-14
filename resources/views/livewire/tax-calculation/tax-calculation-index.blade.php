<div class="card-body p-0">
    <!-- Filter -->
    <div class="bg-light p-3 border-bottom">
        <div class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Tahun</label>
                <select class="form-select" wire:model.live="selectedYear">
                    @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Tarif PPh (%)</label>
                <input type="number" class="form-control" wire:model="taxRate" min="0" max="100" step="0.01">
            </div>
            <div class="col-md-6 d-flex gap-2 align-items-end">
                <button type="button" class="btn btn-primary" wire:click="calculateTax">
                    <i class="ri-calculator-line"></i> Hitung Pajak
                </button>
                @if($showReport)
                <button type="button" class="btn btn-outline-secondary" wire:click="clearReport">
                    <i class="ri-close-line"></i> Reset
                </button>
                @endif
            </div>
        </div>
    </div>

    @if($showReport && $calculation)
    <!-- Calculation Worksheet -->
    <div class="table-responsive">
        <table class="table table-bordered mb-0">
            <thead>
                <tr class="table-dark">
                    <th colspan="2" class="text-center">Perhitungan Pajak Penghasilan Badan Tahun {{ $selectedYear }}</th>
                    <th width="20%" class="text-center">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                {{-- Section 1: Laba/Rugi Komersial --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-bar-chart-box-line me-1"></i> I. LABA/RUGI KOMERSIAL
                    </td>
                </tr>
                <tr>
                    <td width="5%"></td>
                    <td>Total Pendapatan</td>
                    <td class="text-end">{{ number_format($calculation['commercial_detail']['total_pendapatan'], 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td>Total Beban</td>
                    <td class="text-end">({{ number_format($calculation['commercial_detail']['total_beban'], 0, ',', '.') }})</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td></td>
                    <td>{{ $calculation['commercial_profit'] >= 0 ? 'Laba Komersial' : 'Rugi Komersial' }}</td>
                    <td class="text-end {{ $calculation['commercial_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($calculation['commercial_profit'], 0, ',', '.') }}
                    </td>
                </tr>

                {{-- Section 2: Koreksi Fiskal --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-exchange-line me-1"></i> II. KOREKSI FISKAL
                    </td>
                </tr>
                @if($calculation['fiscal_corrections']['items']->count() > 0)
                    {{-- Positive Corrections --}}
                    @if($calculation['fiscal_corrections']['items']->where('correction_type', 'positive')->count() > 0)
                    <tr class="table-success-subtle">
                        <td></td>
                        <td colspan="2" class="fw-medium text-success">Koreksi Positif (+)</td>
                    </tr>
                    @foreach($calculation['fiscal_corrections']['items']->where('correction_type', 'positive') as $item)
                    <tr>
                        <td></td>
                        <td class="ps-4">
                            {{ $item->description }}
                            <span class="badge bg-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }}-subtle text-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }} ms-1">
                                {{ $item->category === 'beda_tetap' ? 'Beda Tetap' : 'Beda Waktu' }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                    <tr class="fw-medium">
                        <td></td>
                        <td class="text-end">Subtotal Koreksi Positif</td>
                        <td class="text-end text-success">{{ number_format($calculation['fiscal_corrections']['total_positive'], 0, ',', '.') }}</td>
                    </tr>
                    @endif

                    {{-- Negative Corrections --}}
                    @if($calculation['fiscal_corrections']['items']->where('correction_type', 'negative')->count() > 0)
                    <tr class="table-danger-subtle">
                        <td></td>
                        <td colspan="2" class="fw-medium text-danger">Koreksi Negatif (-)</td>
                    </tr>
                    @foreach($calculation['fiscal_corrections']['items']->where('correction_type', 'negative') as $item)
                    <tr>
                        <td></td>
                        <td class="ps-4">
                            {{ $item->description }}
                            <span class="badge bg-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }}-subtle text-{{ $item->category === 'beda_tetap' ? 'primary' : 'warning' }} ms-1">
                                {{ $item->category === 'beda_tetap' ? 'Beda Tetap' : 'Beda Waktu' }}
                            </span>
                        </td>
                        <td class="text-end">({{ number_format($item->amount, 0, ',', '.') }})</td>
                    </tr>
                    @endforeach
                    <tr class="fw-medium">
                        <td></td>
                        <td class="text-end">Subtotal Koreksi Negatif</td>
                        <td class="text-end text-danger">({{ number_format($calculation['fiscal_corrections']['total_negative'], 0, ',', '.') }})</td>
                    </tr>
                    @endif
                @else
                <tr>
                    <td></td>
                    <td colspan="2" class="text-muted fst-italic">Tidak ada koreksi fiskal</td>
                </tr>
                @endif

                {{-- Section 3: Laba/Rugi Fiskal --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-funds-line me-1"></i> III. LABA/RUGI FISKAL
                    </td>
                </tr>
                <tr class="table-light fw-bold">
                    <td></td>
                    <td>{{ $calculation['fiscal_profit'] >= 0 ? 'Laba Fiskal' : 'Rugi Fiskal' }}</td>
                    <td class="text-end {{ $calculation['fiscal_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($calculation['fiscal_profit'], 0, ',', '.') }}
                    </td>
                </tr>

                {{-- Section 4: Kompensasi Rugi --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-arrow-left-right-line me-1"></i> IV. KOMPENSASI KERUGIAN
                    </td>
                </tr>
                @if($calculation['loss_compensations']->count() > 0)
                    @foreach($calculation['loss_compensations'] as $lc)
                    <tr>
                        <td></td>
                        <td class="ps-4">
                            Rugi Fiskal Tahun {{ $lc->source_year }}
                            <small class="text-muted">(sisa: Rp {{ number_format($lc->remaining_amount, 0, ',', '.') }}, kadaluarsa: {{ $lc->expires_year }})</small>
                        </td>
                        <td class="text-end">({{ number_format(min($lc->remaining_amount, max(0, $calculation['fiscal_profit'])), 0, ',', '.') }})</td>
                    </tr>
                    @endforeach
                @else
                <tr>
                    <td></td>
                    <td colspan="2" class="text-muted fst-italic">Tidak ada kompensasi kerugian yang tersedia</td>
                </tr>
                @endif
                <tr class="fw-medium">
                    <td></td>
                    <td class="text-end">Total Kompensasi Rugi</td>
                    <td class="text-end">({{ number_format($calculation['loss_compensation_amount'], 0, ',', '.') }})</td>
                </tr>

                {{-- Section 5: PKP --}}
                <tr class="table-primary">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-money-dollar-circle-line me-1"></i> V. PENGHASILAN KENA PAJAK
                    </td>
                </tr>
                <tr class="table-light fw-bold">
                    <td></td>
                    <td>Penghasilan Kena Pajak (PKP)</td>
                    <td class="text-end">{{ number_format($calculation['taxable_income'], 0, ',', '.') }}</td>
                </tr>

                {{-- Section 6: PPh Terutang --}}
                <tr class="table-warning">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-government-line me-1"></i> VI. PAJAK PENGHASILAN TERUTANG
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td>PKP × {{ number_format($calculation['tax_rate'], 2) }}%</td>
                    <td class="text-end">{{ number_format($calculation['taxable_income'], 0, ',', '.') }} × {{ number_format($calculation['tax_rate'], 2) }}%</td>
                </tr>
                <tr class="table-warning fw-bold fs-6">
                    <td></td>
                    <td>PPh Badan Terutang</td>
                    <td class="text-end text-danger">Rp {{ number_format($calculation['tax_amount'], 0, ',', '.') }}</td>
                </tr>

                {{-- Section 7: Laba Bersih Setelah Pajak --}}
                <tr class="table-success">
                    <td colspan="3" class="fw-bold">
                        <i class="ri-hand-coin-line me-1"></i> VII. LABA BERSIH SETELAH PAJAK
                    </td>
                </tr>
                <tr class="table-success fw-bold fs-6">
                    <td></td>
                    <td>Laba Bersih</td>
                    <td class="text-end {{ $calculation['net_income'] >= 0 ? 'text-success' : 'text-danger' }}">
                        Rp {{ number_format($calculation['net_income'], 0, ',', '.') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Action Buttons -->
    <div class="p-3 bg-light border-top">
        <div class="d-flex justify-content-end gap-2 flex-wrap">
            @if($savedCalculation)
                <span class="badge bg-{{ $savedCalculation->isFinalized() ? 'success' : 'warning' }} align-self-center me-2">
                    Status: {{ $savedCalculation->isFinalized() ? 'FINAL' : 'DRAFT' }}
                </span>
            @endif

            @if(!$savedCalculation || !$savedCalculation->isFinalized())
                <button type="button" class="btn btn-info" wire:click="saveTaxCalculation">
                    <i class="ri-save-line me-1"></i> Simpan Perhitungan
                </button>
            @endif

            @if($savedCalculation && !$savedCalculation->hasJournal() && !$savedCalculation->isFinalized())
                <button type="button" class="btn btn-success" wire:click="openJournalModal">
                    <i class="ri-file-text-line me-1"></i> Buat Jurnal Pajak
                </button>
            @endif

            @if($savedCalculation && $savedCalculation->hasJournal() && !$savedCalculation->isFinalized())
                <button type="button" class="btn btn-warning"
                    onclick="Swal.fire({title:'Finalisasi Perhitungan?',text:'Setelah difinalisasi tidak dapat diubah kembali. Kompensasi rugi akan diterapkan.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Finalisasi!',cancelButtonText:'Batal'}).then(r=>{if(r.isConfirmed)$wire.finalizeTaxCalculation()})">
                    <i class="ri-lock-line me-1"></i> Finalisasi
                </button>
            @endif

            @if($savedCalculation && $savedCalculation->hasJournal())
                <span class="badge bg-info align-self-center">
                    <i class="ri-check-line"></i> Jurnal Pajak: {{ $savedCalculation->journalMaster?->journal_no }}
                </span>
            @endif
        </div>
    </div>

    @elseif($showReport && !$calculation)
    <div class="text-center py-5 text-muted">
        <i class="ri-error-warning-line fs-1 d-block mb-2"></i>
        <p>Tidak dapat menghitung pajak. Pastikan ada data transaksi untuk tahun {{ $selectedYear }}.</p>
    </div>
    @else
    <div class="text-center py-5 text-muted">
        <i class="ri-calculator-line fs-1 d-block mb-2"></i>
        <p>Pilih tahun dan klik "Hitung Pajak" untuk memulai perhitungan.</p>
    </div>
    @endif

    <!-- Journal Modal -->
    @if($showJournalModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-file-text-line me-1"></i> Buat Jurnal Pajak</h5>
                    <button type="button" class="btn-close" wire:click="$set('showJournalModal', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info small">
                        <i class="ri-information-line me-1"></i>
                        Jurnal pajak akan dibuat dengan entri:
                        <strong>Dr. Beban Pajak</strong> / <strong>Cr. Utang Pajak</strong>
                        sebesar <strong>Rp {{ number_format($calculation['tax_amount'] ?? 0, 0, ',', '.') }}</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Akun Beban Pajak (Debit) <span class="text-danger">*</span></label>
                        <select class="form-select @error('expenseCoaId') is-invalid @enderror" wire:model="expenseCoaId">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($coas->where('type', 'beban') as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                            @endforeach
                        </select>
                        @error('expenseCoaId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Akun Utang Pajak (Kredit) <span class="text-danger">*</span></label>
                        <select class="form-select @error('liabilityCoaId') is-invalid @enderror" wire:model="liabilityCoaId">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($coas->where('type', 'pasiva') as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                            @endforeach
                        </select>
                        @error('liabilityCoaId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="$set('showJournalModal', false)">Batal</button>
                    <button type="button" class="btn btn-success" wire:click="generateTaxJournal">
                        <i class="ri-check-line me-1"></i> Buat & Posting Jurnal
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
