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
</div>
