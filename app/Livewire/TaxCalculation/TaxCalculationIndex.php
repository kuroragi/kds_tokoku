<?php

namespace App\Livewire\TaxCalculation;

use App\Models\COA;
use App\Models\Period;
use App\Models\TaxCalculation;
use App\Services\TaxService;
use Livewire\Component;

class TaxCalculationIndex extends Component
{
    public $selectedYear;
    public $taxRate = 22.00;
    public $showReport = false;

    // For tax journal generation
    public $showJournalModal = false;
    public $expenseCoaId = '';
    public $liabilityCoaId = '';

    protected TaxService $taxService;

    protected $listeners = [
        'refreshTaxCalculation' => '$refresh',
    ];

    public function boot(TaxService $taxService)
    {
        $this->taxService = $taxService;
    }

    public function mount()
    {
        $this->selectedYear = (int) date('Y');
    }

    public function calculateTax()
    {
        if (!$this->selectedYear) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Pilih tahun terlebih dahulu.']);
            return;
        }
        $this->showReport = true;
    }

    public function getCalculationProperty()
    {
        if (!$this->showReport) return null;
        return $this->taxService->calculateTax($this->selectedYear, $this->taxRate);
    }

    public function getSavedCalculationProperty()
    {
        return TaxCalculation::forYear($this->selectedYear)->first();
    }

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

    public function saveTaxCalculation()
    {
        try {
            $this->taxService->saveTaxCalculation($this->selectedYear, $this->taxRate);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => 'Perhitungan pajak berhasil disimpan.']);
        } catch (\Exception $e) {
            $this->dispatch('showAlert', ['type' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function openJournalModal()
    {
        $saved = $this->savedCalculation;
        if (!$saved) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Simpan perhitungan pajak terlebih dahulu.']);
            return;
        }
        if ($saved->hasJournal()) {
            $this->dispatch('showAlert', ['type' => 'warning', 'message' => 'Jurnal pajak sudah dibuat.']);
            return;
        }
        $this->showJournalModal = true;
    }

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

            $this->showJournalModal = false;
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

    public function clearReport()
    {
        $this->showReport = false;
    }

    public function render()
    {
        return view('livewire.tax-calculation.tax-calculation-index', [
            'calculation' => $this->calculation,
            'savedCalculation' => $this->savedCalculation,
            'availableYears' => $this->availableYears,
            'coas' => $this->coas,
        ]);
    }
}
