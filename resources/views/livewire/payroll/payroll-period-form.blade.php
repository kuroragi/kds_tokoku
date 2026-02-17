<div>
    <div wire:ignore.self class="modal fade" id="payrollPeriodModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form wire:submit="save">
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Payroll Baru</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if($isSuperAdmin)
                        <div class="mb-3">
                            <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                            <select class="form-select @error('businessUnitId') is-invalid @enderror"
                                wire:model="businessUnitId">
                                <option value="">Pilih Unit Usaha</option>
                                @foreach($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            @error('businessUnitId')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Bulan <span class="text-danger">*</span></label>
                                <select class="form-select @error('month') is-invalid @enderror" wire:model="month">
                                    @php
                                    $months = [
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                                        4 => 'April', 5 => 'Mei', 6 => 'Juni',
                                        7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                                        10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                                    ];
                                    @endphp
                                    @foreach($months as $num => $m)
                                    <option value="{{ $num }}">{{ $m }}</option>
                                    @endforeach
                                </select>
                                @error('month')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tahun <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('year') is-invalid @enderror"
                                    wire:model="year" min="2020" max="2099">
                                @error('year')
                                <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3 mt-3">
                            <label class="form-label">Catatan</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror"
                                wire:model="notes" rows="2" placeholder="Catatan opsional..."></textarea>
                            @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <span wire:loading wire:target="save" class="spinner-border spinner-border-sm me-1"></span>
                            Buat Payroll
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @script
    <script>
        $wire.on('openPayrollPeriodModal', () => {
            new bootstrap.Modal(document.getElementById('payrollPeriodModal')).show();
        });
        $wire.on('closePayrollPeriodModal', () => {
            bootstrap.Modal.getInstance(document.getElementById('payrollPeriodModal'))?.hide();
        });
    </script>
    @endscript
</div>
