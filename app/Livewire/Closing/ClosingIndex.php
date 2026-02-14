<?php

namespace App\Livewire\Closing;

use App\Models\COA;
use App\Models\Period;
use App\Services\ClosingService;
use Livewire\Component;

class ClosingIndex extends Component
{
    public $selectedYear;

    // Yearly closing form
    public $summaryCoaId = '';
    public $retainedEarningsCoaId = '';

    protected ClosingService $closingService;

    protected $listeners = [
        'closeMonthConfirmed' => 'closeMonth',
        'reopenMonthConfirmed' => 'reopenMonth',
    ];

    public function boot(ClosingService $closingService)
    {
        $this->closingService = $closingService;
    }

    public function mount()
    {
        $this->selectedYear = (int) date('Y');
    }

    // Computed properties
    public function getYearStatusProperty()
    {
        return $this->closingService->getYearClosingStatus($this->selectedYear);
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

    // Monthly closing
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

    // Yearly closing
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

    public function render()
    {
        return view('livewire.closing.closing-index', [
            'yearStatus' => $this->yearStatus,
            'availableYears' => $this->availableYears,
            'coas' => $this->coas,
        ]);
    }
}
