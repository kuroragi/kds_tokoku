<?php

namespace App\Livewire\GeneralLedger;

use App\Models\COA;
use App\Models\Journal;
use App\Models\Period;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class GeneralLedgerIndex extends Component
{
    // Filter Properties
    public $filterPeriod = '';
    public $filterCoa = '';
    public $filterCoaType = '';
    public $dateFrom = '';
    public $dateTo = '';

    // UI State
    public $selectedCoa = null;
    public $showDetail = false;

    /**
     * Reset pagination/state when filters change
     */
    public function updatedFilterPeriod()
    {
        $this->resetDetail();
    }

    public function updatedFilterCoa()
    {
        $this->resetDetail();
    }

    public function updatedFilterCoaType()
    {
        $this->filterCoa = '';
        $this->resetDetail();
    }

    public function updatedDateFrom()
    {
        $this->resetDetail();
    }

    public function updatedDateTo()
    {
        $this->resetDetail();
    }

    /**
     * View detail ledger for a specific COA
     */
    public function viewDetail($coaId)
    {
        $this->selectedCoa = COA::find($coaId);
        $this->showDetail = true;
    }

    /**
     * Go back to summary view
     */
    public function backToSummary()
    {
        $this->resetDetail();
    }

    private function resetDetail()
    {
        $this->selectedCoa = null;
        $this->showDetail = false;
    }

    /**
     * Get summary data: per-COA totals for the selected period/filters
     */
    public function getSummaryDataProperty()
    {
        $query = Journal::query()
            ->join('journal_masters', 'journals.id_journal_master', '=', 'journal_masters.id')
            ->join('c_o_a_s', 'journals.id_coa', '=', 'c_o_a_s.id')
            ->leftJoin('periods', 'journal_masters.id_period', '=', 'periods.id')
            ->where('journal_masters.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereNull('journal_masters.deleted_at')
            ->whereNull('c_o_a_s.deleted_at')
            ->where('c_o_a_s.is_active', true);

        $this->applyFilters($query);

        return $query->select([
                'c_o_a_s.id as coa_id',
                'c_o_a_s.code as coa_code',
                'c_o_a_s.name as coa_name',
                'c_o_a_s.type as coa_type',
                DB::raw('COUNT(journals.id) as total_transactions'),
                DB::raw('SUM(journals.debit) as total_debit'),
                DB::raw('SUM(journals.credit) as total_credit'),
                DB::raw('(SUM(journals.debit) - SUM(journals.credit)) as balance'),
            ])
            ->groupBy('c_o_a_s.id', 'c_o_a_s.code', 'c_o_a_s.name', 'c_o_a_s.type')
            ->orderBy('c_o_a_s.code')
            ->get();
    }

    /**
     * Get detail ledger entries for the selected COA
     */
    public function getDetailDataProperty()
    {
        if (!$this->selectedCoa) {
            return collect();
        }

        $query = Journal::query()
            ->join('journal_masters', 'journals.id_journal_master', '=', 'journal_masters.id')
            ->leftJoin('periods', 'journal_masters.id_period', '=', 'periods.id')
            ->where('journals.id_coa', $this->selectedCoa->id)
            ->where('journal_masters.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereNull('journal_masters.deleted_at');

        $this->applyFilters($query);

        $entries = $query->select([
                'journals.id',
                'journal_masters.journal_no',
                'journal_masters.journal_date',
                'journal_masters.reference',
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
     * Apply common filters to query
     */
    private function applyFilters($query)
    {
        if ($this->filterPeriod) {
            $query->where('journal_masters.id_period', $this->filterPeriod);
        }

        if ($this->filterCoa && !$this->showDetail) {
            $query->where('journals.id_coa', $this->filterCoa);
        }

        if ($this->filterCoaType && !$this->showDetail) {
            $query->where('c_o_a_s.type', $this->filterCoaType);
        }

        if ($this->dateFrom) {
            $query->whereDate('journal_masters.journal_date', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('journal_masters.journal_date', '<=', $this->dateTo);
        }
    }

    /**
     * Get grand totals for summary
     */
    public function getGrandTotalDebitProperty()
    {
        return $this->summaryData->sum('total_debit');
    }

    public function getGrandTotalCreditProperty()
    {
        return $this->summaryData->sum('total_credit');
    }

    /**
     * Clear all filters
     */
    public function clearFilters()
    {
        $this->filterPeriod = '';
        $this->filterCoa = '';
        $this->filterCoaType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetDetail();
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
     * Get COA list for filter
     */
    public function getCoasProperty()
    {
        $query = COA::active()->leafAccounts()->orderBy('code');

        if ($this->filterCoaType) {
            $query->where('type', $this->filterCoaType);
        }

        return $query->get();
    }

    /**
     * Get COA types for filter
     */
    public function getCoaTypesProperty()
    {
        return [
            'aktiva' => 'Aktiva',
            'pasiva' => 'Pasiva',
            'modal' => 'Modal',
            'pendapatan' => 'Pendapatan',
            'beban' => 'Beban',
        ];
    }

    public function render()
    {
        return view('livewire.general-ledger.general-ledger-index', [
            'summaryData' => $this->summaryData,
            'detailData' => $this->detailData,
            'periods' => $this->periods,
            'coas' => $this->coas,
            'coaTypes' => $this->coaTypes,
            'grandTotalDebit' => $this->grandTotalDebit,
            'grandTotalCredit' => $this->grandTotalCredit,
        ]);
    }
}
