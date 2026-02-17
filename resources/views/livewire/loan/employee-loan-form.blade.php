<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5)">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="ri-hand-coin-line me-1"></i>
                        Buat Pinjaman Karyawan
                    </h5>
                    <button type="button" class="btn-close" wire:click="$set('showModal', false)"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body">
                        <div class="row g-3">
                            {{-- Business Unit --}}
                            @if($isSuperAdmin)
                            <div class="col-md-6">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                <select class="form-select @error('business_unit_id') is-invalid @enderror"
                                    wire:model.live="business_unit_id">
                                    <option value="">Pilih Unit Usaha</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            @endif

                            {{-- Employee --}}
                            <div class="col-md-6">
                                <label class="form-label">Karyawan <span class="text-danger">*</span></label>
                                <select class="form-select @error('employee_id') is-invalid @enderror"
                                    wire:model="employee_id">
                                    <option value="">Pilih Karyawan</option>
                                    @foreach($employees as $emp)
                                    <option value="{{ $emp->id }}">{{ $emp->code }} - {{ $emp->name }}</option>
                                    @endforeach
                                </select>
                                @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Loan Number --}}
                            <div class="col-md-6">
                                <label class="form-label">No. Pinjaman</label>
                                <input type="text" class="form-control bg-light" wire:model="loan_number" readonly>
                            </div>

                            {{-- Loan Amount --}}
                            <div class="col-md-6">
                                <label class="form-label">Jumlah Pinjaman <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control @error('loan_amount') is-invalid @enderror"
                                        wire:model.live="loan_amount" min="1" placeholder="0">
                                    @error('loan_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            {{-- Installment Count --}}
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Cicilan (bulan) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('installment_count') is-invalid @enderror"
                                    wire:model.live="installment_count" min="1" max="60" placeholder="12">
                                @error('installment_count') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Installment Amount Preview --}}
                            <div class="col-md-4">
                                <label class="form-label">Cicilan per Bulan</label>
                                <input type="text" class="form-control bg-light fw-semibold text-primary" readonly
                                    value="{{ $this->installment_amount ? 'Rp ' . number_format($this->installment_amount) : '-' }}">
                            </div>

                            {{-- Disbursed Date --}}
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Pencairan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('disbursed_date') is-invalid @enderror"
                                    wire:model="disbursed_date">
                                @error('disbursed_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Start Deduction Date --}}
                            <div class="col-md-6">
                                <label class="form-label">Mulai Potong dari Payroll</label>
                                <input type="date" class="form-control" wire:model="start_deduction_date">
                                <small class="text-muted">Kosongkan = mulai potong dari bulan pencairan</small>
                            </div>

                            {{-- Payment COA --}}
                            <div class="col-md-6">
                                <label class="form-label">Akun Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-select @error('payment_coa_id') is-invalid @enderror"
                                    wire:model="payment_coa_id">
                                    <option value="">Pilih Akun Kas/Bank</option>
                                    @foreach($cashAccounts as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->code }} - {{ $coa->name }}</option>
                                    @endforeach
                                </select>
                                @error('payment_coa_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Description --}}
                            <div class="col-md-6">
                                <label class="form-label">Keterangan</label>
                                <input type="text" class="form-control" wire:model="description"
                                    placeholder="Contoh: Kasbon untuk keperluan...">
                            </div>

                            {{-- Notes --}}
                            <div class="col-md-6">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control" wire:model="notes" rows="1"></textarea>
                            </div>
                        </div>

                        {{-- Info Box --}}
                        @if($this->installment_amount > 0)
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="ri-information-line me-1"></i>
                            Pinjaman sebesar <strong>Rp {{ number_format((int)$loan_amount) }}</strong>
                            akan dicicil <strong>{{ $installment_count }}x</strong>
                            sebesar <strong>Rp {{ number_format($this->installment_amount) }}/bulan</strong>.
                            Potongan akan otomatis masuk saat payroll dihitung.
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="$set('showModal', false)">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-save-line me-1"></i> Cairkan Pinjaman
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
