{{-- Step 3: Jurnal Pajak & Finalisasi --}}
<div class="p-3">
    <h6 class="fw-bold mb-3">
        <i class="ri-file-text-line text-info me-2"></i>
        Jurnal Pajak & Finalisasi — {{ $selectedYear }}
    </h6>

    @if(!$savedCalculation)
        {{-- No saved calculation --}}
        <div class="alert alert-warning">
            <i class="ri-error-warning-line me-1"></i>
            Simpan perhitungan pajak terlebih dahulu di <strong>Langkah 2 — Perhitungan Pajak</strong>.
        </div>
        <div class="text-center">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="goToStep(2)">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Perhitungan Pajak
            </button>
        </div>
    @else
        {{-- Saved calculation summary --}}
        <div class="card border mb-3">
            <div class="card-header bg-light py-2">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 small fw-bold">Ringkasan Perhitungan</h6>
                    <span class="badge bg-{{ $savedCalculation->isFinalized() ? 'success' : 'warning' }}">
                        {{ $savedCalculation->isFinalized() ? 'FINAL' : 'DRAFT' }}
                    </span>
                </div>
            </div>
            <div class="card-body py-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="50%">Laba Komersial</td>
                        <td class="text-end">Rp {{ number_format($savedCalculation->commercial_profit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Laba Fiskal</td>
                        <td class="text-end">Rp {{ number_format($savedCalculation->fiscal_profit, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">PKP</td>
                        <td class="text-end">Rp {{ number_format($savedCalculation->taxable_income, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="fw-bold border-top">
                        <td>PPh Terutang</td>
                        <td class="text-end text-danger">Rp {{ number_format($savedCalculation->tax_amount, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        {{-- Journal Generation --}}
        @if($savedCalculation->tax_amount > 0 && !$savedCalculation->hasJournal())
        <div class="card border mb-3">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 small fw-bold"><i class="ri-file-add-line me-1"></i> Buat Jurnal Pajak</h6>
            </div>
            <div class="card-body">
                <div class="alert alert-info small py-2">
                    <i class="ri-information-line me-1"></i>
                    Jurnal pajak: <strong>Dr. Beban Pajak</strong> / <strong>Cr. Utang Pajak</strong>
                    sebesar <strong>Rp {{ number_format($savedCalculation->tax_amount, 0, ',', '.') }}</strong>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small">Akun Beban Pajak (Debit) <span class="text-danger">*</span></label>
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
                    <div class="col-md-6">
                        <label class="form-label small">Akun Utang Pajak (Kredit) <span class="text-danger">*</span></label>
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
                <div class="text-end">
                    <button type="button" class="btn btn-success" wire:click="generateTaxJournal">
                        <i class="ri-check-line me-1"></i> Buat & Posting Jurnal
                    </button>
                </div>
            </div>
        </div>
        @elseif($savedCalculation->hasJournal())
        {{-- Journal exists --}}
        <div class="alert alert-success mb-3">
            <i class="ri-checkbox-circle-line me-1"></i>
            Jurnal pajak sudah dibuat: <strong>{{ $savedCalculation->journalMaster?->journal_no }}</strong>
        </div>
        @elseif($savedCalculation->tax_amount == 0)
        <div class="alert alert-info mb-3">
            <i class="ri-information-line me-1"></i>
            PPh Terutang = Rp 0. Tidak diperlukan jurnal pajak.
        </div>
        @endif

        {{-- Finalization --}}
        @if(!$savedCalculation->isFinalized())
            @if($savedCalculation->hasJournal() || $savedCalculation->tax_amount == 0)
            <div class="card border border-warning">
                <div class="card-body text-center">
                    <p class="text-muted small mb-2">
                        <i class="ri-lock-line me-1"></i>
                        Setelah difinalisasi, perhitungan tidak dapat diubah kembali. Kompensasi rugi akan diterapkan otomatis.
                    </p>
                    <button type="button" class="btn btn-warning"
                        onclick="Swal.fire({title:'Finalisasi Perhitungan?',text:'Setelah difinalisasi tidak dapat diubah kembali. Kompensasi rugi akan diterapkan.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Finalisasi!',cancelButtonText:'Batal'}).then(r=>{if(r.isConfirmed)$wire.finalizeTaxCalculation()})">
                        <i class="ri-lock-line me-1"></i> Finalisasi Perhitungan
                    </button>
                </div>
            </div>
            @else
            <div class="alert alert-secondary small">
                <i class="ri-time-line me-1"></i>
                Buat jurnal pajak terlebih dahulu sebelum melakukan finalisasi.
            </div>
            @endif
        @else
        {{-- Already finalized --}}
        <div class="alert alert-success">
            <i class="ri-shield-check-line me-1"></i>
            <strong>Perhitungan pajak tahun {{ $selectedYear }} telah difinalisasi</strong>
            pada {{ $savedCalculation->finalized_at ? $savedCalculation->finalized_at->format('d/m/Y H:i') : '-' }}.
        </div>
        @endif
    @endif
</div>
