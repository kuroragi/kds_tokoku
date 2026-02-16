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

    /**
     * Reset COA filter when COA type changes
     */
    public function updatedFilterCoaType()
    {
        $this->filterCoa = '';
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
     * Apply common filters to query
     */
    private function applyFilters($query)
    {
        if ($this->filterPeriod) {
            $query->where('journal_masters.id_period', $this->filterPeriod);
        }

        if ($this->filterCoa) {
            $query->where('journals.id_coa', $this->filterCoa);
        }

        if ($this->filterCoaType) {
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

    /**
     * Get PDF download URL
     */
    public function getDownloadUrlProperty(): string
    {
        $params = array_filter([
            'period_id' => $this->filterPeriod ?: null,
            'date_from' => $this->dateFrom ?: null,
            'date_to' => $this->dateTo ?: null,
            'coa_id' => $this->filterCoa ?: null,
            'coa_type' => $this->filterCoaType ?: null,
        ]);

        $query = http_build_query($params);

        return route('report.pdf.general-ledger') . ($query ? '?' . $query : '');
    }

    public function render()
    {
        return view('livewire.general-ledger.general-ledger-index', [
            'summaryData' => $this->summaryData,
            'periods' => $this->periods,
            'coas' => $this->coas,
            'coaTypes' => $this->coaTypes,
            'grandTotalDebit' => $this->grandTotalDebit,
            'grandTotalCredit' => $this->grandTotalCredit,
            'downloadUrl' => $this->downloadUrl,
        ]);
    }
}
