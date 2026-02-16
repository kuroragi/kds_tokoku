<?php

namespace App\Livewire\TrialBalance;

use App\Models\Period;
use App\Services\FinancialReportService;
use Livewire\Component;

class TrialBalanceIndex extends Component
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
     * Get the balance sheet data (used by the blade)
     */
    public function getReportDataProperty()
    {
        if (!$this->showReport) {
            return null;
        }

        return $this->reportService->getBalanceSheet($this->getFilters());
    }

    /**
     * Get the trial balance data (saldo per akun)
     */
    public function getTrialBalanceDataProperty()
    {
        if (!$this->showReport) {
            return null;
        }

        return $this->reportService->getTrialBalance($this->getFilters());
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

    /**
     * Get PDF download URLs
     */
    public function getDownloadUrlsProperty(): array
    {
        $params = http_build_query($this->getFilters());

        return [
            'balance_sheet' => route('report.pdf.balance-sheet') . ($params ? '?' . $params : ''),
            'trial_balance' => route('report.pdf.trial-balance') . ($params ? '?' . $params : ''),
        ];
    }

    public function render()
    {
        return view('livewire.trial-balance.trial-balance-index', [
            'reportData' => $this->reportData,
            'trialBalanceData' => $this->trialBalanceData,
            'periods' => $this->periods,
            'downloadUrls' => $this->downloadUrls,
        ]);
    }
}
