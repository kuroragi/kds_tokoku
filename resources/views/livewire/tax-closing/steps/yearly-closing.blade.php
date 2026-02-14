{{-- Step 5: Closing Tahunan --}}
<div class="p-3">
    <h6 class="fw-bold mb-3">
        <i class="ri-book-line text-danger me-2"></i>
        Tutup Buku Tahunan — {{ $selectedYear }}
    </h6>

    {{-- Prerequisites --}}
    <div class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="alert py-2 mb-0 {{ $yearStatus['all_months_closed'] ? 'alert-success' : 'alert-danger' }}">
                    <small>
                        <i class="ri-{{ $yearStatus['all_months_closed'] ? 'checkbox-circle' : 'close-circle' }}-line me-1"></i>
                        Semua periode ditutup
                    </small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert py-2 mb-0 {{ ($savedCalculation && $savedCalculation->isFinalized()) ? 'alert-success' : 'alert-secondary' }}">
                    <small>
                        <i class="ri-{{ ($savedCalculation && $savedCalculation->isFinalized()) ? 'checkbox-circle' : 'circle' }}-line me-1"></i>
                        Pajak difinalisasi
                    </small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="alert py-2 mb-0 {{ $yearStatus['has_closing_journal'] ? 'alert-info' : 'alert-secondary' }}">
                    <small>
                        <i class="ri-{{ $yearStatus['has_closing_journal'] ? 'checkbox-circle' : 'circle' }}-line me-1"></i>
                        @if($yearStatus['has_closing_journal'])
                            Jurnal penutup: {{ $yearStatus['closing_journal']->journal_no }}
                        @else
                            Jurnal penutup belum dibuat
                        @endif
                    </small>
                </div>
            </div>
        </div>
    </div>

    @if(!$yearStatus['has_closing_journal'])
        @if($yearStatus['all_months_closed'])
        {{-- Closing Form --}}
        <div class="card border">
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Pilih akun untuk jurnal penutup. Sistem akan menutup semua akun Pendapatan dan Beban ke akun Ikhtisar Laba Rugi,
                    lalu menutup saldo ke Laba Ditahan.
                </p>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Akun Ikhtisar Laba Rugi <span class="text-danger">*</span></label>
                        <select class="form-select @error('summaryCoaId') is-invalid @enderror" wire:model="summaryCoaId">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($coas as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                            @endforeach
                        </select>
                        @error('summaryCoaId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Akun Laba Ditahan <span class="text-danger">*</span></label>
                        <select class="form-select @error('retainedEarningsCoaId') is-invalid @enderror" wire:model="retainedEarningsCoaId">
                            <option value="">-- Pilih Akun --</option>
                            @foreach($coas->where('type', 'modal') as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                            @endforeach
                        </select>
                        @error('retainedEarningsCoaId')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="text-end">
                    <button type="button" class="btn btn-danger"
                        onclick="Swal.fire({title:'Tutup Buku Tahunan '+{{ $selectedYear }}+'?',text:'Jurnal penutup akan dibuat otomatis. Pastikan semua transaksi dan pajak sudah dicatat.',icon:'warning',showCancelButton:true,confirmButtonColor:'#d33',confirmButtonText:'Ya, Tutup Buku!',cancelButtonText:'Batal'}).then(r=>{if(r.isConfirmed)$wire.closeYear()})">
                        <i class="ri-book-line me-1"></i> Tutup Buku Tahunan {{ $selectedYear }}
                    </button>
                </div>
            </div>
        </div>
        @else
        <div class="alert alert-warning">
            <i class="ri-error-warning-line me-1"></i>
            Tutup semua periode bulanan terlebih dahulu di <strong>Langkah 4</strong> sebelum melakukan closing tahunan.
        </div>
        <div class="text-center">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="goToStep(4)">
                <i class="ri-arrow-left-line me-1"></i> Kembali ke Closing Bulanan
            </button>
        </div>
        @endif
    @else
        <div class="alert alert-success">
            <i class="ri-checkbox-circle-line me-1"></i>
            <strong>Buku tahun {{ $selectedYear }} telah ditutup.</strong>
            Jurnal penutup: <strong>{{ $yearStatus['closing_journal']->journal_no }}</strong>
            pada tanggal {{ \Carbon\Carbon::parse($yearStatus['closing_journal']->journal_date)->format('d/m/Y') }}.
        </div>
    @endif
</div>
