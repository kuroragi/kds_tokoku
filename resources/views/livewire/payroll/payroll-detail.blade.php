<div>
    {{-- Back & Title --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('payroll.index') }}" class="btn btn-light btn-sm">
                <i class="ri-arrow-left-line"></i> Kembali
            </a>
            <div>
                <h5 class="mb-0">{{ $payrollPeriod->name }}</h5>
                <small class="text-muted">{{ $payrollPeriod->businessUnit->name ?? '-' }}</small>
            </div>
            <span class="badge bg-{{ \App\Models\PayrollPeriod::STATUS_COLORS[$payrollPeriod->status] ?? 'secondary' }} ms-2">
                {{ \App\Models\PayrollPeriod::STATUSES[$payrollPeriod->status] ?? $payrollPeriod->status }}
            </span>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex gap-2">
            @if($payrollPeriod->canCalculate())
            <button class="btn btn-info btn-sm" wire:click="calculate"
                wire:confirm="Hitung ulang payroll? Data detail yang ada akan dihitung ulang.">
                <span wire:loading wire:target="calculate" class="spinner-border spinner-border-sm me-1"></span>
                <i class="ri-calculator-line"></i> Hitung
            </button>
            @endif

            @if($payrollPeriod->canApprove())
            <button class="btn btn-warning btn-sm" wire:click="approve"
                wire:confirm="Setujui payroll ini? Setelah disetujui tidak bisa diiubah kecuali di-void.">
                <span wire:loading wire:target="approve" class="spinner-border spinner-border-sm me-1"></span>
                <i class="ri-checkbox-circle-line"></i> Setujui
            </button>
            @endif

            @if($payrollPeriod->canPay())
            <button class="btn btn-success btn-sm" wire:click="openPaymentForm">
                <i class="ri-money-dollar-circle-line"></i> Bayar
            </button>
            @endif

            @if($payrollPeriod->canVoid())
            <button class="btn btn-outline-danger btn-sm" wire:click="voidPayroll"
                wire:confirm="Batalkan payroll ini? Jurnal terkait akan di-void.">
                <span wire:loading wire:target="voidPayroll" class="spinner-border spinner-border-sm me-1"></span>
                <i class="ri-close-circle-line"></i> Void
            </button>
            @endif
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-3">
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Pendapatan</div>
                            <div class="fw-bold text-primary">Rp {{ number_format($payrollPeriod->total_earnings) }}</div>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-3 p-2">
                            <i class="ri-money-dollar-circle-line text-primary" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Tunjangan Perusahaan</div>
                            <div class="fw-bold text-info">Rp {{ number_format($payrollPeriod->total_benefits) }}</div>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-3 p-2">
                            <i class="ri-shield-check-line text-info" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Potongan</div>
                            <div class="fw-bold text-danger">Rp {{ number_format($payrollPeriod->total_deductions) }}</div>
                        </div>
                        <div class="bg-danger bg-opacity-10 rounded-3 p-2">
                            <i class="ri-subtract-line text-danger" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Gaji Bersih</div>
                            <div class="fw-bold text-success">Rp {{ number_format($payrollPeriod->total_net) }}</div>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-3 p-2">
                            <i class="ri-wallet-3-line text-success" style="font-size: 1.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Form Modal --}}
    @if($showPaymentForm)
    <div class="card border-0 shadow-sm mb-3 border-success border-top border-3">
        <div class="card-body">
            <h6 class="card-title"><i class="ri-bank-card-line"></i> Form Pembayaran</h6>
            <div class="row g-3 align-items-end">
                <div class="col-lg-6">
                    <label class="form-label">Akun Pembayaran <span class="text-danger">*</span></label>
                    <select class="form-select @error('paymentCoaId') is-invalid @enderror" wire:model="paymentCoaId">
                        <option value="">Pilih akun kas/bank...</option>
                        @foreach($paymentCoas as $coa)
                        <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                        @endforeach
                    </select>
                    @error('paymentCoaId')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-lg-6">
                    <button class="btn btn-success" wire:click="pay"
                        wire:confirm="Bayar payroll dan buat jurnal? Pastikan akun kas/bank sudah benar.">
                        <span wire:loading wire:target="pay" class="spinner-border spinner-border-sm me-1"></span>
                        Bayar & Buat Jurnal
                    </button>
                    <button class="btn btn-light ms-2" wire:click="$set('showPaymentForm', false)">Batal</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Notes & Journal reference --}}
    @if($payrollPeriod->notes || $payrollPeriod->journalMaster)
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex gap-4">
                @if($payrollPeriod->notes)
                <div><small class="text-muted">Catatan:</small> <small>{{ $payrollPeriod->notes }}</small></div>
                @endif
                @if($payrollPeriod->journalMaster)
                <div><small class="text-muted">Jurnal:</small>
                    <small class="fw-medium">{{ $payrollPeriod->journalMaster->reference }}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- Employee Entries --}}
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="ri-team-line me-1"></i> Detail Karyawan ({{ $entries->count() }})</h6>
                <div class="input-group input-group-sm" style="width: 250px;">
                    <span class="input-group-text bg-white"><i class="ri-search-line"></i></span>
                    <input type="text" class="form-control" wire:model.live.debounce.300ms="search"
                        placeholder="Cari karyawan...">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="3%" class="ps-3"></th>
                        <th width="5%">#</th>
                        <th width="20%">Karyawan</th>
                        <th width="12%">Jabatan</th>
                        <th width="13%" class="text-end">Pendapatan</th>
                        <th width="13%" class="text-end">Tunjangan</th>
                        <th width="13%" class="text-end">Potongan</th>
                        <th width="13%" class="text-end">Gaji Bersih</th>
                        <th width="8%" class="text-center pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $idx => $entry)
                    <tr wire:key="entry-{{ $entry->id }}">
                        <td class="ps-3">
                            <button class="btn btn-sm btn-link p-0" data-bs-toggle="collapse"
                                data-bs-target="#entry-detail-{{ $entry->id }}">
                                <i class="ri-arrow-down-s-line"></i>
                            </button>
                        </td>
                        <td class="text-muted small">{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-medium">{{ $entry->employee->name ?? '-' }}</div>
                            <small class="text-muted">{{ $entry->employee->code ?? '' }}</small>
                        </td>
                        <td class="small text-muted">{{ $entry->employee->position->name ?? '-' }}</td>
                        <td class="text-end small">Rp {{ number_format($entry->total_earnings) }}</td>
                        <td class="text-end small">Rp {{ number_format($entry->total_benefits) }}</td>
                        <td class="text-end small text-danger">Rp {{ number_format($entry->total_deductions) }}</td>
                        <td class="text-end small fw-bold">Rp {{ number_format($entry->net_salary) }}</td>
                        <td class="text-center pe-3">
                            <div class="btn-group btn-group-sm">
                                @if($payrollPeriod->canCalculate())
                                <button class="btn btn-outline-primary" title="Tambah Item Manual"
                                    wire:click="openManualForm({{ $entry->id }})">
                                    <i class="ri-add-line"></i>
                                </button>
                                @endif
                                <button class="btn btn-outline-info" title="Slip Gaji"
                                    wire:click="$dispatch('openPayrollSlip', { entryId: {{ $entry->id }} })">
                                    <i class="ri-file-text-line"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    {{-- Expandable detail --}}
                    <tr class="collapse" id="entry-detail-{{ $entry->id }}">
                        <td colspan="9" class="bg-light p-0">
                            <div class="p-3">
                                <div class="row g-3">
                                    {{-- Pendapatan --}}
                                    <div class="col-md-4">
                                        <h6 class="small fw-bold text-primary mb-2">
                                            <i class="ri-add-circle-line"></i> Pendapatan
                                        </h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            @foreach($entry->details->where('type', 'earning') as $detail)
                                            <tr>
                                                <td class="small py-1">{{ $detail->component_name }}</td>
                                                <td class="small py-1 text-end">Rp {{ number_format($detail->amount) }}</td>
                                                <td class="py-1" width="24">
                                                    @if($detail->is_manual && $payrollPeriod->canCalculate())
                                                    <button class="btn btn-link btn-sm p-0 text-danger"
                                                        wire:click="removeManualItem({{ $detail->id }})"
                                                        wire:confirm="Hapus item manual ini?">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </div>

                                    {{-- Tunjangan Perusahaan --}}
                                    <div class="col-md-4">
                                        <h6 class="small fw-bold text-info mb-2">
                                            <i class="ri-shield-check-line"></i> Tunjangan Perusahaan
                                        </h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            @foreach($entry->details->where('type', 'benefit') as $detail)
                                            <tr>
                                                <td class="small py-1">{{ $detail->component_name }}</td>
                                                <td class="small py-1 text-end">Rp {{ number_format($detail->amount) }}</td>
                                                <td class="py-1" width="24">
                                                    @if($detail->is_manual && $payrollPeriod->canCalculate())
                                                    <button class="btn btn-link btn-sm p-0 text-danger"
                                                        wire:click="removeManualItem({{ $detail->id }})"
                                                        wire:confirm="Hapus item manual ini?">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </div>

                                    {{-- Potongan --}}
                                    <div class="col-md-4">
                                        <h6 class="small fw-bold text-danger mb-2">
                                            <i class="ri-subtract-line"></i> Potongan
                                        </h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            @foreach($entry->details->where('type', 'deduction') as $detail)
                                            <tr>
                                                <td class="small py-1">{{ $detail->component_name }}</td>
                                                <td class="small py-1 text-end">Rp {{ number_format($detail->amount) }}</td>
                                                <td class="py-1" width="24">
                                                    @if($detail->is_manual && $payrollPeriod->canCalculate())
                                                    <button class="btn btn-link btn-sm p-0 text-danger"
                                                        wire:click="removeManualItem({{ $detail->id }})"
                                                        wire:confirm="Hapus item manual ini?">
                                                        <i class="ri-close-line"></i>
                                                    </button>
                                                    @endif
                                                </td>
                                            </tr>
                                            @endforeach
                                        </table>
                                    </div>
                                </div>

                                {{-- PPh21 & Summary --}}
                                <div class="mt-2 pt-2 border-top d-flex justify-content-between">
                                    <div class="small">
                                        <span class="text-muted">PPh21:</span>
                                        <span class="fw-medium text-danger">Rp {{ number_format($entry->pph21_amount) }}</span>
                                        @if($entry->pph21_rate > 0)
                                        <span class="text-muted">(TER {{ number_format($entry->pph21_rate, 2) }}%)</span>
                                        @endif
                                    </div>
                                    <div class="small">
                                        <span class="text-muted">Bruto:</span>
                                        <span class="fw-medium">Rp {{ number_format($entry->gross_salary) }}</span>
                                        <span class="text-muted ms-3">Netto:</span>
                                        <span class="fw-bold text-success">Rp {{ number_format($entry->net_salary) }}</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-5">
                            <div class="text-muted">
                                <i class="ri-team-line" style="font-size: 3rem; opacity: 0.3;"></i>
                                <p class="mt-2 mb-0">
                                    @if($payrollPeriod->isDraft())
                                    Klik tombol <strong>"Hitung"</strong> untuk menghitung payroll karyawan
                                    @else
                                    Belum ada data karyawan
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Manual Item Modal --}}
    @if($showManualForm)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item Manual</h5>
                    <button type="button" class="btn-close" wire:click="$set('showManualForm', false)"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Komponen (opsional)</label>
                        <select class="form-select" wire:model.live="manualComponentId">
                            <option value="">-- Manual input --</option>
                            @foreach($manualComponents as $comp)
                            <option value="{{ $comp->id }}">{{ $comp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Komponen <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('manualComponentName') is-invalid @enderror"
                            wire:model="manualComponentName" placeholder="cth: Lembur 10 jam">
                        @error('manualComponentName')
                        <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Tipe</label>
                            <select class="form-select" wire:model="manualType">
                                <option value="earning">Pendapatan</option>
                                <option value="deduction">Potongan</option>
                                <option value="benefit">Tunjangan</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" wire:model="manualCategory">
                                @foreach(\App\Models\SalaryComponent::CATEGORIES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nominal <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control @error('manualAmount') is-invalid @enderror"
                                wire:model="manualAmount" min="1">
                        </div>
                        @error('manualAmount')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" wire:model="manualNotes" rows="2"
                            placeholder="Catatan opsional..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="$set('showManualForm', false)">Batal</button>
                    <button type="button" class="btn btn-primary" wire:click="addManualItem">
                        <span wire:loading wire:target="addManualItem" class="spinner-border spinner-border-sm me-1"></span>
                        Tambah
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
