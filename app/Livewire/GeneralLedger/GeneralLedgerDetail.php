<?php

namespace App\Livewire\GeneralLedger;

use App\Models\COA;
use App\Models\Journal;
use App\Models\Period;
use App\Services\BusinessUnitService;
use Livewire\Component;

class GeneralLedgerDetail extends Component
{
    // COA being viewed
    public $coa;

    // Filter Properties
    public $filterPeriod = '';
    public $dateFrom = '';
    public $dateTo = '';

    /**
     * Mount the component with the COA model
     */
    public function mount(COA $coa)
    {
        $this->coa = $coa;
    }

    /**
     * Get detail ledger entries for the selected COA
     */
    public function getDetailDataProperty()
    {
        $query = Journal::query()
            ->join('journal_masters', 'journals.id_journal_master', '=', 'journal_masters.id')
            ->leftJoin('periods', 'journal_masters.id_period', '=', 'periods.id')
            ->where('journals.id_coa', $this->coa->id)
            ->where('journal_masters.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereNull('journal_masters.deleted_at');

        // Apply business unit scoping
        if (!BusinessUnitService::isSuperAdmin()) {
            $unitId = BusinessUnitService::getUserBusinessUnitId();
            if ($unitId) {
                $query->where('journal_masters.business_unit_id', $unitId);
            }
        }

        if ($this->filterPeriod) {
            $query->where('journal_masters.id_period', $this->filterPeriod);
        }

        if ($this->dateFrom) {
            $query->whereDate('journal_masters.journal_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('journal_masters.journal_date', '<=', $this->dateTo);
        }

        $entries = $query->select([
                'journals.id',
                'journal_masters.journal_no',
                'journal_masters.journal_date',
                'journal_masters.reference',
                'journal_masters.description as journal_description',
                'journals.description',
                'journals.debit',
                'journals.credit',
                'periods.name as period_name',
            ])
            ->orderBy('journal_masters.journal_date')
            ->orderBy('journal_masters.journal_no')
            ->orderBy('journals.sequence')
            ->get();

        // Calculate running balance
        $runningBalance = 0;
        foreach ($entries as $entry) {
            $runningBalance += ($entry->debit - $entry->credit);
            $entry->running_balance = $runningBalance;
        }

        return $entries;
    }

    /**
     * Get total debit for the current view
     */
    public function getTotalDebitProperty()
    {
        return $this->detailData->sum('debit');
    }

    /**
     * Get total credit for the current view
     */
    public function getTotalCreditProperty()
    {
        return $this->detailData->sum('credit');
    }

    /**
     * Get final balance
     */
    public function getFinalBalanceProperty()
    {
        return $this->totalDebit - $this->totalCredit;
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->filterPeriod = '';
        $this->dateFrom = '';
        $this->dateTo = '';
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
     * Get PDF download URL
     */
    public function getDownloadUrlProperty(): string
    {
        $params = array_filter([
            'period_id' => $this->filterPeriod ?: null,
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
        ]);

        $query = http_build_query($params);

        return route('report.pdf.general-ledger.detail', $this->coa) . ($query ? '?' . $query : '');
    }

    public function render()
    {
        return view('livewire.general-ledger.general-ledger-detail', [
            'detailData' => $this->detailData,
            'periods' => $this->periods,
            'totalDebit' => $this->totalDebit,
            'totalCredit' => $this->totalCredit,
            'finalBalance' => $this->finalBalance,
            'downloadUrl' => $this->downloadUrl,
        ]);
    }
}
