{{-- Step 4: Closing Bulanan --}}
<div class="p-3">
    <h6 class="fw-bold mb-3">
        <i class="ri-calendar-check-line text-primary me-2"></i>
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
