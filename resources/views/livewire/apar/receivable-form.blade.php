<div>
    @if($showModal)
    <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.5);">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white py-2">
                    <h6 class="modal-title">
                        <i class="ri-{{ $isEditing ? 'pencil' : 'add' }}-line me-1"></i>
                        {{ $isEditing ? 'Edit Piutang' : 'Tambah Piutang' }}
                    </h6>
                    <button type="button" class="btn-close btn-close-white btn-sm" wire:click="closeModal"></button>
                </div>
                <form wire:submit="save">
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div class="row g-3">
                            {{-- Unit Usaha --}}
                            <div class="col-md-6">
                                <label class="form-label">Unit Usaha <span class="text-danger">*</span></label>
                                @if($isSuperAdmin)
                                <select class="form-select @error('business_unit_id') is-invalid @enderror"
                                    wire:model.live="business_unit_id" {{ $isEditing ? 'disabled' : '' }}>
                                    <option value="">-- Pilih Unit --</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @else
                                <input type="text" class="form-control" value="{{ $units->first()?->name }}" readonly>
                                @endif
                                @error('business_unit_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Customer --}}
                            <div class="col-md-6">
                                <label class="form-label">Pelanggan <span class="text-danger">*</span></label>
                                <select class="form-select @error('customer_id') is-invalid @enderror"
                                    wire:model="customer_id">
                                    <option value="">-- Pilih Pelanggan --</option>
                                    @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->code }} — {{ $customer->name }}</option>
                                    @endforeach
                                </select>
                                @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Invoice Number --}}
                            <div class="col-md-4">
                                <label class="form-label">No. Faktur <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('invoice_number') is-invalid @enderror"
                                    wire:model="invoice_number" placeholder="INV-001">
                                @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Invoice Date --}}
                            <div class="col-md-4">
                                <label class="form-label">Tanggal Faktur <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('invoice_date') is-invalid @enderror"
                                    wire:model="invoice_date">
                                @error('invoice_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Due Date --}}
                            <div class="col-md-4">
                                <label class="form-label">Jatuh Tempo <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('due_date') is-invalid @enderror"
                                    wire:model="due_date">
                                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Credit COA --}}
                            <div class="col-md-6">
                                <label class="form-label">Akun Pendapatan <span class="text-danger">*</span></label>
                                <select class="form-select @error('credit_coa_id') is-invalid @enderror"
                                    wire:model="credit_coa_id">
                                    <option value="">-- Pilih Akun --</option>
                                    @foreach($coaOptions as $coa)
                                    <option value="{{ $coa->id }}">{{ $coa->code }} — {{ $coa->name }}</option>
                                    @endforeach
                                </select>
                                @error('credit_coa_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted">Akun pendapatan yang akan dikredit</small>
                            </div>

                            {{-- Amount --}}
                            <div class="col-md-6">
                                <label class="form-label">Jumlah (Rp) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control @error('amount') is-invalid @enderror"
                                    wire:model="amount" placeholder="0" min="1">
                                @error('amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Description --}}
                            <div class="col-md-12">
                                <label class="form-label">Deskripsi</label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror"
                                    wire:model="description" placeholder="Keterangan piutang">
                                @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            {{-- Notes --}}
                            <div class="col-md-12">
                                <label class="form-label">Catatan</label>
                                <textarea class="form-control @error('notes') is-invalid @enderror"
                                    wire:model="notes" rows="2" placeholder="Catatan tambahan"></textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="closeModal">
                            <i class="ri-close-line"></i> Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm" wire:loading.attr="disabled">
                            <span wire:loading.remove><i class="ri-save-line"></i> Simpan</span>
                            <span wire:loading><i class="ri-loader-4-line"></i> Menyimpan...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>
