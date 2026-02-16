<?php

namespace App\Livewire\TaxClosing;

use App\Models\COA;
use App\Models\FiscalCorrection;
use App\Models\Period;
use App\Models\TaxCalculation;
use App\Services\ClosingService;
use App\Services\TaxService;
use Livewire\Component;

class TaxClosingWizard extends Component
{
    // ======================================
    // Wizard Navigation
    // ======================================
    public int $currentStep = 1;
    public int $selectedYear;
    public int $totalSteps = 5;

    // ======================================
    // Step 1: Koreksi Fiskal
    // ======================================
    public $showModal = false;
    public $isEditing = false;
    public $editId = null;
    public $description = '';
    public $correction_type = 'positive';
    public $category = 'beda_tetap';
    public $amount = 0;
    public $notes = '';

    // ======================================
    // Step 2: Perhitungan Pajak
    // ======================================
    public $taxRate = 22.00;
    public $showTaxReport = false;

    // ======================================
    // Step 3: Jurnal Pajak
    // ======================================
    public $expenseCoaId = '';
    public $liabilityCoaId = '';

    // ======================================
    // Step 5: Closing Tahunan
    // ======================================
    public $summaryCoaId = '';
    public $retainedEarningsCoaId = '';

    // ======================================
    // Services
    // ======================================
    protected TaxService $taxService;
    protected ClosingService $closingService;

    protected $listeners = [
        'deleteFiscalCorrection' => 'deleteFiscalCorrection',
    ];

    public function boot(TaxService $taxService, ClosingService $closingService)
    {
        $this->taxService = $taxService;
        $this->closingService = $closingService;
    }

    public function mount()
    {
        $this->selectedYear = (int) date('Y');
        $this->detectCurrentStep();
    }

    public function updatedSelectedYear()
    {
        $this->showTaxReport = false;
        $this->detectCurrentStep();
    }

    /**
     * Detect the first incomplete step and set as current step.
     * If no progress beyond step 1, start at step 1.
     */
    private function detectCurrentStep()
    {
        $statuses = $this->stepStatuses;

        // If no progress beyond step 1 (no tax calculation saved), start at step 1
        if (!$statuses[2]) {
            $this->currentStep = 1;
            return;
        }

        // Find the first incomplete step from step 2 onward
        for ($step = 2; $step <= $this->totalSteps; $step++) {
            if (!$statuses[$step]) {
                $this->currentStep = $step;
                return;
            }
        }

        // All steps complete — show the last step
        $this->currentStep = $this->totalSteps;
    }

    // ======================================
    // Navigation
    // ======================================

    public function goToStep(int $step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    public function nextStep()
    {
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function prevStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    // ======================================
    // Computed Properties (Shared)
    // ======================================

    public function getAvailableYearsProperty()
    {
        return Period::select('year')
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');
    }

    public function getCoasProperty()
    {
        return COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->orderBy('code')
            ->get();
    }

    // Step 1
    public function getCorrectionsProperty()
    {
        return FiscalCorrection::forYear($this->selectedYear)
            ->orderBy('correction_type')
            ->orderBy('category')
            ->get();
    }

    public function getCorrectionSummaryProperty()
    {
        $corrections = $this->corrections;
        return [
            'total_positive' => $corrections->where('correction_type', 'positive')->sum('amount'),
            'total_negative' => $corrections->where('correction_type', 'negative')->sum('amount'),
            'count' => $corrections->count(),
        ];
    }

    // Step 2
    public function getCalculationProperty()
    {
        if (!$this->showTaxReport) return null;
        return $this->taxService->calculateTax($this->selectedYear, $this->taxRate);
    }

    public function getSavedCalculationProperty()
    {
        return TaxCalculation::forYear($this->selectedYear)->first();
    }

    // Step 4-5
    public function getYearStatusProperty()
    {
        return $this->closingService->getYearClosingStatus($this->selectedYear);
    }

    // Step completions
    public function getStepStatusesProperty(): array
    {
        $saved = $this->savedCalculation;
        $yearStatus = $this->yearStatus;

        return [
            1 => true,
            2 => $saved !== null,
            3 => $saved !== null && $saved->isFinalized(),
            4 => $yearStatus['all_months_closed'],
            5 => $yearStatus['has_closing_journal'],
        ];
    }

    // ======================================
    // Step 1: Koreksi Fiskal
    // ======================================

    public function openModal()
    {
        $this->resetFiscalForm();
        $this->showModal = true;
    }

    public function editFiscalCorrection($id)
    {
        $correction = FiscalCorrection::findOrFail($id);
        $this->isEditing = true;
        $this->editId = $correction->id;
        $this->description = $correction->description;
        $this->correction_type = $correction->correction_type;
        $this->category = $correction->category;
        $this->amount = $correction->amount;
        $this->notes = $correction->notes ?? '';
        $this->showModal = true;
    }

    public function saveFiscalCorrection()
    {
        $this->validate([
            'description' => 'required|string|max:255',
            'correction_type' => 'required|in:positive,negative',
            'category' => 'required|in:beda_tetap,beda_waktu',
            'amount' => 'required|numeric|min:1',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'year' => $this->selectedYear,
            'description' => $this->description,
            'correction_type' => $this->correction_type,
            'category' => $this->category,
            'amount' => $this->amount,
            'notes' => $this->notes,
        ];

        if ($this->isEditing) {
            FiscalCorrection::findOrFail($this->editId)->update($data);
            $message = 'Koreksi fiskal berhasil diperbarui.';
        } else {
            FiscalCorrection::create($data);
            $message = 'Koreksi fiskal berhasil ditambahkan.';
        }

        $this->dispatch('showAlert', ['type' => 'success', 'message' => $message]);
        $this->closeModal();
    }

    public function deleteFiscalCorrection($id)
    {
        FiscalCorrection::findOrFail($id)->delete();
        $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Koreksi fiskal berhasil dihapus.']);
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetFiscalForm();
    }

    private function resetFiscalForm()
    {
        $this->isEditing = false;
        $this->editId = null;
        $this->description = '';
        $this->correction_type = 'positive';
        $this->category = 'beda_tetap';
        $this->amount = 0;
        $this->notes = '';
        $this->resetErrorBag();
    }

    // ======================================
    // Step 2: Perhitungan Pajak
    // ======================================

    public function calculateTax()
    {
        $this->showTaxReport = true;
    }

    public function saveTaxCalculation()
    {
        try {
            $this->taxService->saveTaxCalculation($this->selectedYear, $this->taxRate);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Perhitungan pajak berhasil disimpan.']);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function clearTaxReport()
    {
        $this->showTaxReport = false;
    }

    // ======================================
    // Step 3: Jurnal Pajak & Finalisasi
    // ======================================

    public function generateTaxJournal()
    {
        $this->validate([
            'expenseCoaId' => 'required|exists:c_o_a_s,id',
            'liabilityCoaId' => 'required|exists:c_o_a_s,id',
        ], [
            'expenseCoaId.required' => 'Pilih akun Beban Pajak.',
            'liabilityCoaId.required' => 'Pilih akun Utang Pajak.',
        ]);

        try {
            $expenseCoa = COA::findOrFail($this->expenseCoaId);
            $liabilityCoa = COA::findOrFail($this->liabilityCoaId);

            $this->taxService->generateTaxJournal(
                $this->selectedYear,
                $expenseCoa->code,
                $liabilityCoa->code
            );

            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Jurnal pajak berhasil dibuat dan diposting.']);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function finalizeTaxCalculation()
    {
        try {
            $this->taxService->finalizeTaxCalculation($this->selectedYear);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Perhitungan pajak berhasil difinalisasi.']);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ======================================
    // Confirmation Modals
    // ======================================
    public $showConfirmModal = false;
    public $confirmAction = '';
    public $confirmPeriodId = null;
    public $confirmPeriodName = '';

    public function confirmCloseMonth($periodId)
    {
        $period = Period::find($periodId);
        $this->confirmAction = 'closeMonth';
        $this->confirmPeriodId = $periodId;
        $this->confirmPeriodName = $period ? $period->name : '';
        $this->showConfirmModal = true;
    }

    public function confirmReopenMonth($periodId)
    {
        $period = Period::find($periodId);
        $this->confirmAction = 'reopenMonth';
        $this->confirmPeriodId = $periodId;
        $this->confirmPeriodName = $period ? $period->name : '';
        $this->showConfirmModal = true;
    }

    public function confirmCloseYear()
    {
        $this->confirmAction = 'closeYear';
        $this->confirmPeriodId = null;
        $this->confirmPeriodName = '';
        $this->showConfirmModal = true;
    }

    public function dismissConfirmModal()
    {
        $this->showConfirmModal = false;
        $this->confirmAction = '';
        $this->confirmPeriodId = null;
        $this->confirmPeriodName = '';
    }

    public function executeConfirmedAction()
    {
        $this->showConfirmModal = false;

        match ($this->confirmAction) {
            'closeMonth' => $this->closeMonth($this->confirmPeriodId),
            'reopenMonth' => $this->reopenMonth($this->confirmPeriodId),
            'closeYear' => $this->closeYear(),
            default => null,
        };

        $this->confirmAction = '';
        $this->confirmPeriodId = null;
        $this->confirmPeriodName = '';
    }

    // ======================================
    // Step 4: Closing Bulanan
    // ======================================

    public function closeMonth($periodId)
    {
        try {
            $this->closingService->closeMonth($periodId);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Periode berhasil ditutup.']);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function reopenMonth($periodId)
    {
        try {
            $this->closingService->reopenMonth($periodId);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Periode berhasil dibuka kembali.']);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ======================================
    // Step 5: Closing Tahunan
    // ======================================

    public function closeYear()
    {
        $this->validate([
            'summaryCoaId' => 'required|exists:c_o_a_s,id',
            'retainedEarningsCoaId' => 'required|exists:c_o_a_s,id',
        ], [
            'summaryCoaId.required' => 'Pilih akun Ikhtisar Laba Rugi.',
            'retainedEarningsCoaId.required' => 'Pilih akun Laba Ditahan.',
        ]);

        try {
            $summaryCoa = COA::findOrFail($this->summaryCoaId);
            $retainedCoa = COA::findOrFail($this->retainedEarningsCoaId);

            $journal = $this->closingService->closeYear(
                $this->selectedYear,
                $summaryCoa->code,
                $retainedCoa->code
            );

            $this->dispatch('showAlert', [
                'type' => 'success',
                'message' => "Closing tahunan {$this->selectedYear} berhasil. Jurnal: {$journal->journal_no}",
            ]);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    // ======================================
    // Render
    // ======================================

    public function render()
    {
        return view('livewire.tax-closing.tax-closing-wizard', [
            'corrections' => $this->corrections,
            'correctionSummary' => $this->correctionSummary,
            'calculation' => $this->calculation,
            'savedCalculation' => $this->savedCalculation,
            'yearStatus' => $this->yearStatus,
            'availableYears' => $this->availableYears,
            'coas' => $this->coas,
            'stepStatuses' => $this->stepStatuses,
        ]);
    }
}
