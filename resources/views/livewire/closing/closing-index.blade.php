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
        </div>
    </div>

    {{-- Section 1: Monthly Closing --}}
    <div class="p-3">
        <h6 class="fw-bold mb-3">
            <i class="ri-calendar-check-line me-1 text-primary"></i>
            Status Periode Bulanan — {{ $selectedYear }}
        </h6>

        <div class="table-responsive">
            <table class="table table-hover table-sm table-bordered mb-0">
                <thead>
                    <tr class="table-dark">
                        <th width="5%">#</th>
                        <th width="25%">Periode</th>
                        <th width="15%">Mulai</th>
                        <th width="15%">Selesai</th>
                        <th width="15%" class="text-center">Status</th>
                        <th width="15%">Ditutup Pada</th>
                        <th width="10%" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($yearStatus['periods'] as $index => $period)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $period->name }}</td>
                        <td>{{ \Carbon\Carbon::parse($period->start_date)->format('d/m/Y') }}</td>
                        <td>{{ \Carbon\Carbon::parse($period->end_date)->format('d/m/Y') }}</td>
                        <td class="text-center">
                            @if($period->is_closed)
                                <span class="badge bg-danger"><i class="ri-lock-line me-1"></i>Ditutup</span>
                            @else
                                <span class="badge bg-success"><i class="ri-lock-unlock-line me-1"></i>Terbuka</span>
                            @endif
                        </td>
                        <td>
                            @if($period->closed_at)
                                <small class="text-muted">{{ \Carbon\Carbon::parse($period->closed_at)->format('d/m/Y H:i') }}</small>
                            @else
                                <small class="text-muted">-</small>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($period->is_closed)
                                <button class="btn btn-sm btn-outline-success"
                                    onclick="Swal.fire({title:'Buka Kembali Periode?',text:'Jurnal bisa kembali diinput ke periode ini.',icon:'question',showCancelButton:true,confirmButtonText:'Ya, Buka!',cancelButtonText:'Batal'}).then(r=>{if(r.isConfirmed)$wire.dispatch('reopenMonthConfirmed',[{{ $period->id }}])})"
                                    title="Buka Kembali">
                                    <i class="ri-lock-unlock-line"></i>
                                </button>
                            @else
                                <button class="btn btn-sm btn-outline-danger"
                                    onclick="Swal.fire({title:'Tutup Periode?',text:'Setelah ditutup, jurnal tidak dapat ditambahkan ke periode ini.',icon:'warning',showCancelButton:true,confirmButtonText:'Ya, Tutup!',cancelButtonText:'Batal'}).then(r=>{if(r.isConfirmed)$wire.dispatch('closeMonthConfirmed',[{{ $period->id }}])})"
                                    title="Tutup Periode">
                                    <i class="ri-lock-line"></i>
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="ri-calendar-line fs-3 d-block mb-2"></i>
                            Tidak ada periode untuk tahun {{ $selectedYear }}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Monthly status summary --}}
        @if($yearStatus['periods']->count() > 0)
        <div class="mt-3">
            <div class="alert {{ $yearStatus['all_months_closed'] ? 'alert-success' : 'alert-warning' }} py-2">
                @if($yearStatus['all_months_closed'])
                    <i class="ri-checkbox-circle-line me-1"></i>
                    <strong>Semua periode bulanan tahun {{ $selectedYear }} telah ditutup.</strong>
                @else
                    @php
                        $openCount = $yearStatus['periods']->where('is_closed', false)->count();
                        $closedCount = $yearStatus['periods']->where('is_closed', true)->count();
                    @endphp
                    <i class="ri-error-warning-line me-1"></i>
                    <strong>{{ $closedCount }} dari {{ $yearStatus['periods']->count() }} periode telah ditutup.</strong>
                    {{ $openCount }} periode masih terbuka.
                @endif
            </div>
        </div>
        @endif
    </div>

    <hr class="my-0">

    {{-- Section 2: Yearly Closing --}}
    <div class="p-3">
        <h6 class="fw-bold mb-3">
            <i class="ri-book-line me-1 text-danger"></i>
            Tutup Buku Tahunan — {{ $selectedYear }}
        </h6>

        {{-- Prerequisites --}}
        <div class="mb-3">
            <div class="row g-2">
                <div class="col-md-6">
                    <div class="alert py-2 mb-0 {{ $yearStatus['all_months_closed'] ? 'alert-success' : 'alert-danger' }}">
                        <small>
                            <i class="ri-{{ $yearStatus['all_months_closed'] ? 'checkbox-circle' : 'close-circle' }}-line me-1"></i>
                            Semua periode bulanan ditutup
                        </small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert py-2 mb-0 {{ $yearStatus['has_closing_journal'] ? 'alert-info' : 'alert-secondary' }}">
                        <small>
                            <i class="ri-{{ $yearStatus['has_closing_journal'] ? 'checkbox-circle' : 'circle' }}-line me-1"></i>
                            @if($yearStatus['has_closing_journal'])
                                Jurnal penutup sudah dibuat: {{ $yearStatus['closing_journal']->journal_no }}
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
                Tutup semua periode bulanan terlebih dahulu sebelum melakukan closing tahunan.
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
</div>
