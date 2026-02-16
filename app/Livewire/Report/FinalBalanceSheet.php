<?php

namespace App\Livewire\Report;

use App\Models\Period;
use App\Services\FinancialReportService;
use Livewire\Component;

class FinalBalanceSheet extends Component
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
     * Get balance sheet data
     */
    public function getBalanceSheetDataProperty()
    {
        if (!$this->showReport) {
            return null;
        }

        return $this->reportService->getBalanceSheet($this->getFilters());
    }

    /**
     * Get income statement data
     */
    public function getIncomeStatementDataProperty()
    {
        if (!$this->showReport) {
            return null;
        }

        return $this->reportService->getIncomeStatement($this->getFilters());
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
    public function getFilters(): array
    {
        return array_filter([
            'period_id' => $this->filterPeriod ?: null,
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
        ]);
    }

    /**
     * Get PDF download URL
     */
    public function getDownloadUrlProperty(): string
    {
        $params = http_build_query($this->getFilters());

        return route('report.pdf.final-balance-sheet') . ($params ? '?' . $params : '');
    }

    public function render()
    {
        return view('livewire.report.final-balance-sheet', [
            'balanceSheetData' => $this->balanceSheetData,
            'incomeStatementData' => $this->incomeStatementData,
            'periods' => $this->periods,
            'downloadUrl' => $this->downloadUrl,
        ]);
    }
}
