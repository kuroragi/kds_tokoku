<?php

namespace App\Livewire\AdjustedTrialBalance;

use App\Models\Period;
use App\Services\FinancialReportService;
use Livewire\Component;

class AdjustedTrialBalanceIndex extends Component
{
    // Filter Properties
    public $filterPeriod = '';
    public $dateFrom = '';
    public $dateTo = '';

    // State
    public $showReport = false;

    protected FinancialReportService $reportService;

    public function boot(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Generate / refresh the report
     */
    public function generateReport()
    {
        if (!$this->filterPeriod && !$this->dateFrom && !$this->dateTo) {
            $this->dispatch('alert', type: 'warning', message: 'Pilih periode atau range tanggal terlebih dahulu.');
            return;
        }

        $this->showReport = true;
    }

    /**
     * Get the adjusted trial balance data
     */
    public function getReportDataProperty()
    {
        if (!$this->showReport) {
            return null;
        }

        return $this->reportService->getAdjustedTrialBalance($this->getFilters());
    }

    /**
     * Reset report
     */
    public function clearFilters()
    {
        $this->filterPeriod = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->showReport = false;
    }

    /**
     * Get periods for filter
     */
    public function getPeriodsProperty()
    {
        return Period::orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();
    }

    /**
     * Build filters array
     */
    private function getFilters(): array
    {
        return array_filter([
            'period_id' => $this->filterPeriod ?: null,
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
        ]);
    }

    public function render()
    {
        return view('livewire.adjusted-trial-balance.adjusted-trial-balance-index', [
            'reportData' => $this->reportData,
            'periods' => $this->periods,
        ]);
    }
}
