<div>
    {{-- Year Selector --}}
    <div class="bg-light p-3 border-bottom">
        <div class="row align-items-center">
            <div class="col-md-3">
                <label class="form-label small fw-medium mb-1">Tahun Fiskal</label>
                <select class="form-select" wire:model.live="selectedYear">
                    @foreach($availableYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-9 text-end text-muted small pt-3">
                Langkah {{ $currentStep }} dari {{ $totalSteps }}
            </div>
        </div>
    </div>

    {{-- Stepper --}}
    <div class="p-3 border-bottom">
        @php
            $steps = [
                1 => ['icon' => 'ri-exchange-line', 'label' => 'Koreksi Fiskal'],
                2 => ['icon' => 'ri-calculator-line', 'label' => 'Perhitungan Pajak'],
                3 => ['icon' => 'ri-file-text-line', 'label' => 'Jurnal & Finalisasi'],
                4 => ['icon' => 'ri-calendar-check-line', 'label' => 'Closing Bulanan'],
                5 => ['icon' => 'ri-book-line', 'label' => 'Closing Tahunan'],
            ];
        @endphp
        <div class="d-flex justify-content-between position-relative">
            {{-- Connecting line --}}
            <div class="position-absolute" style="top: 20px; left: 10%; right: 10%; height: 2px; background: #dee2e6; z-index: 0;"></div>
            @foreach($steps as $num => $step)
            <div class="text-center flex-fill position-relative" wire:click="goToStep({{ $num }})" style="cursor: pointer; z-index: 1;">
                <div class="mx-auto d-flex align-items-center justify-content-center rounded-circle
                    @if($currentStep === $num)
                        bg-primary text-white shadow-sm
                    @elseif($stepStatuses[$num])
                        bg-success text-white
                    @else
                        bg-white text-muted border
                    @endif"
                    style="width: 40px; height: 40px; transition: all 0.2s;">
                    @if($stepStatuses[$num] && $currentStep !== $num)
                        <i class="ri-check-line fw-bold"></i>
                    @else
                        <i class="{{ $step['icon'] }}"></i>
                    @endif
                </div>
                <small class="d-none d-md-block mt-1 {{ $currentStep === $num ? 'fw-bold text-primary' : 'text-muted' }}">
                    {{ $step['label'] }}
                </small>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Step Content --}}
    <div style="min-height: 300px;">
        @if($currentStep === 1)
            @include('livewire.tax-closing.steps.fiscal-correction')
        @elseif($currentStep === 2)
            @include('livewire.tax-closing.steps.tax-calculation')
        @elseif($currentStep === 3)
            @include('livewire.tax-closing.steps.tax-journal')
        @elseif($currentStep === 4)
            @include('livewire.tax-closing.steps.monthly-closing')
        @elseif($currentStep === 5)
            @include('livewire.tax-closing.steps.yearly-closing')
        @endif
    </div>

    {{-- Navigation --}}
    <div class="p-3 bg-light border-top d-flex justify-content-between">
        <button class="btn btn-outline-secondary" wire:click="prevStep" @if($currentStep === 1) disabled @endif>
            <i class="ri-arrow-left-line me-1"></i> Sebelumnya
        </button>
        <button class="btn btn-primary" wire:click="nextStep" @if($currentStep === $totalSteps) disabled @endif>
            Selanjutnya <i class="ri-arrow-right-line ms-1"></i>
        </button>
    </div>

    {{-- Confirmation Modal --}}
    @if($showConfirmModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);" wire:click.self="dismissConfirmModal">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-body text-center p-4">
                    @if($confirmAction === 'closeMonth')
                        <div class="mb-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="ri-lock-line text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2">Tutup Periode?</h5>
                        <p class="text-muted mb-1">Anda akan menutup periode:</p>
                        <p class="fw-semibold text-dark mb-3">{{ $confirmPeriodName }}</p>
                        <p class="text-muted small mb-0">Setelah ditutup, jurnal tidak dapat ditambahkan ke periode ini. Anda masih bisa membuka kembali periode nanti jika diperlukan.</p>
                    @elseif($confirmAction === 'reopenMonth')
                        <div class="mb-3">
                            <div class="rounded-circle bg-success bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="ri-lock-unlock-line text-success" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2">Buka Kembali Periode?</h5>
                        <p class="text-muted mb-1">Anda akan membuka kembali periode:</p>
                        <p class="fw-semibold text-dark mb-3">{{ $confirmPeriodName }}</p>
                        <p class="text-muted small mb-0">Jurnal baru bisa kembali diinput ke periode ini setelah dibuka.</p>
                    @elseif($confirmAction === 'closeYear')
                        <div class="mb-3">
                            <div class="rounded-circle bg-danger bg-opacity-10 d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="ri-book-line text-danger" style="font-size: 2.5rem;"></i>
                            </div>
                        </div>
                        <h5 class="fw-bold mb-2">Tutup Buku Tahunan {{ $selectedYear }}?</h5>
                        <p class="text-muted mb-3">Jurnal penutup akan dibuat otomatis oleh sistem. Semua akun Pendapatan dan Beban akan ditutup ke akun Ikhtisar Laba Rugi, lalu saldo dipindahkan ke Laba Ditahan.</p>
                        <div class="alert alert-warning py-2 small text-start mb-0">
                            <i class="ri-error-warning-line me-1"></i>
                            Pastikan semua transaksi dan pajak untuk tahun {{ $selectedYear }} sudah dicatat dengan benar sebelum melanjutkan.
                        </div>
                    @endif
                </div>
                <div class="modal-footer justify-content-center border-top-0 pt-0 pb-4">
                    <button type="button" class="btn btn-light px-4" wire:click="dismissConfirmModal">
                        <i class="ri-close-line me-1"></i> Batal
                    </button>
                    @if($confirmAction === 'closeMonth')
                        <button type="button" class="btn btn-danger px-4" wire:click="executeConfirmedAction">
                            <i class="ri-lock-line me-1"></i> Ya, Tutup Periode
                        </button>
                    @elseif($confirmAction === 'reopenMonth')
                        <button type="button" class="btn btn-success px-4" wire:click="executeConfirmedAction">
                            <i class="ri-lock-unlock-line me-1"></i> Ya, Buka Kembali
                        </button>
                    @elseif($confirmAction === 'closeYear')
                        <button type="button" class="btn btn-danger px-4" wire:click="executeConfirmedAction">
                            <i class="ri-book-line me-1"></i> Ya, Tutup Buku
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
