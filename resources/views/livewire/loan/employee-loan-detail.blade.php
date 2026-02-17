<div>
    {{-- Loan Info Card --}}
    <div class="row mb-3">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h5 class="mb-1">{{ $loan->loan_number }}</h5>
                            <p class="text-muted mb-0">{{ $loan->description ?: 'Pinjaman Karyawan' }}</p>
                        </div>
                        <span class="badge bg-{{ \App\Models\EmployeeLoan::STATUS_COLORS[$loan->status] ?? 'secondary' }} fs-6">
                            {{ \App\Models\EmployeeLoan::STATUSES[$loan->status] ?? $loan->status }}
                        </span>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" width="40%">Karyawan</td>
                                    <td class="fw-medium">{{ $loan->employee->name ?? '-' }}
                                        <span class="text-muted small">({{ $loan->employee->code ?? '' }})</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Tanggal Cair</td>
                                    <td>{{ $loan->disbursed_date->format('d/m/Y') }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Mulai Potong</td>
                                    <td>{{ $loan->start_deduction_date ? $loan->start_deduction_date->format('d/m/Y') : 'Sejak pencairan' }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Akun Cair</td>
                                    <td>{{ $loan->paymentCoa->name ?? '-' }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm table-borderless mb-0">
                                <tr>
                                    <td class="text-muted" width="40%">Total Pinjaman</td>
                                    <td class="fw-semibold">Rp {{ number_format($loan->loan_amount) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Cicilan/Bulan</td>
                                    <td>Rp {{ number_format($loan->installment_amount) }} x {{ $loan->installment_count }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Sudah Dibayar</td>
                                    <td class="text-success">Rp {{ number_format($loan->total_paid) }}</td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Sisa</td>
                                    <td class="fw-bold {{ $loan->remaining_amount > 0 ? 'text-danger' : 'text-success' }}">
                                        Rp {{ number_format($loan->remaining_amount) }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-3">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-muted">Progress Pelunasan</span>
                            <span class="fw-medium">{{ $loan->progress_percent }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ $loan->progress_percent }}%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Panel --}}
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h6 class="card-title mb-3">Aksi</h6>
                    @if($loan->status === 'active')
                        <button class="btn btn-primary btn-sm w-100 mb-2" wire:click="openPaymentForm">
                            <i class="ri-money-dollar-circle-line me-1"></i> Bayar Cicilan
                        </button>
                        @if($loan->remaining_amount > $loan->installment_amount)
                        <button class="btn btn-success btn-sm w-100 mb-2" wire:click="payFull">
                            <i class="ri-check-double-line me-1"></i> Lunasi Semua
                        </button>
                        @endif
                        @if($loan->total_paid == 0)
                        <button class="btn btn-soft-danger btn-sm w-100"
                            wire:click="voidLoan"
                            wire:confirm="Batalkan pinjaman ini?">
                            <i class="ri-close-circle-line me-1"></i> Batalkan Pinjaman
                        </button>
                        @endif
                    @elseif($loan->status === 'paid_off')
                        <div class="text-center text-success py-3">
                            <i class="ri-check-double-fill fs-1 d-block mb-2"></i>
                            <strong>Pinjaman Lunas</strong>
                        </div>
                    @else
                        <div class="text-center text-muted py-3">
                            <i class="ri-close-circle-fill fs-1 d-block mb-2"></i>
                            <strong>Pinjaman Dibatalkan</strong>
                        </div>
                    @endif

                    @if($loan->notes)
                    <hr>
                    <div class="small text-muted">
                        <strong>Catatan:</strong> {{ $loan->notes }}
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Manual Payment Form --}}
    @if($showPaymentForm)
    <div class="card border-0 shadow-sm mb-3 border-start border-primary border-3">
        <div class="card-body">
            <h6 class="card-title mb-3"><i class="ri-money-dollar-circle-line me-1"></i> Pembayaran Manual</h6>
            <form wire:submit="submitPayment">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Jumlah <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control @error('paymentAmount') is-invalid @enderror"
                                wire:model="paymentAmount" min="1">
                            @error('paymentAmount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('paymentDate') is-invalid @enderror"
                            wire:model="paymentDate">
                        @error('paymentDate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Akun Kas/Bank <span class="text-danger">*</span></label>
                        <select class="form-select @error('paymentCoaId') is-invalid @enderror"
                            wire:model="paymentCoaId">
                            <option value="">Pilih Akun</option>
                            @foreach($cashAccounts as $coa)
                            <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                            @endforeach
                        </select>
                        @error('paymentCoaId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Referensi</label>
                        <input type="text" class="form-control" wire:model="paymentReference">
                    </div>
                    <div class="col-md-2 d-flex align-items-end gap-1">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="ri-save-line me-1"></i> Simpan
                        </button>
                        <button type="button" class="btn btn-light btn-sm"
                            wire:click="$set('showPaymentForm', false)">Batal</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Payments History --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h6 class="mb-0"><i class="ri-history-line me-1"></i> Riwayat Pembayaran</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="5%" class="ps-3">#</th>
                        <th width="15%">Tanggal</th>
                        <th width="15%" class="text-end">Jumlah</th>
                        <th width="15%">Sumber</th>
                        <th width="15%">Referensi</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $i => $payment)
                    <tr>
                        <td class="ps-3 text-muted small">{{ $i + 1 }}</td>
                        <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                        <td class="text-end fw-medium text-success">Rp {{ number_format($payment->amount) }}</td>
                        <td>
                            @if($payment->is_from_payroll)
                            <span class="badge bg-info">Payroll</span>
                            @else
                            <span class="badge bg-warning">Manual</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $payment->reference ?? '-' }}</td>
                        <td class="small text-muted">{{ $payment->notes ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Belum ada pembayaran.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
