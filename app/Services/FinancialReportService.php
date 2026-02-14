<?php

namespace App\Services;

use App\Models\COA;
use App\Models\Journal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * Get Trial Balance data (Neraca Saldo)
     * Returns per-account debit/credit totals from posted journals
     *
     * @param array $filters ['period_id' => ?, 'date_from' => ?, 'date_to' => ?]
     * @return array ['accounts' => Collection, 'total_debit' => float, 'total_credit' => float]
     */
    public function getTrialBalance(array $filters = []): array
    {
        $query = $this->baseQuery();
        $this->applyFilters($query, $filters);

        $accounts = $query->select([
                'c_o_a_s.id as coa_id',
                'c_o_a_s.code as coa_code',
                'c_o_a_s.name as coa_name',
                'c_o_a_s.type as coa_type',
                DB::raw('SUM(journals.debit) as total_debit'),
                DB::raw('SUM(journals.credit) as total_credit'),
                DB::raw('(SUM(journals.debit) - SUM(journals.credit)) as balance'),
            ])
            ->groupBy('c_o_a_s.id', 'c_o_a_s.code', 'c_o_a_s.name', 'c_o_a_s.type')
            ->orderBy('c_o_a_s.code')
            ->get();

        // Normalize: debit-normal accounts (aktiva, beban) show balance on debit side
        //            credit-normal accounts (pasiva, modal, pendapatan) show balance on credit side
        $accounts->each(function ($account) {
            $balance = $account->balance;
            if (in_array($account->coa_type, ['aktiva', 'beban'])) {
                $account->saldo_debit = $balance >= 0 ? $balance : 0;
                $account->saldo_credit = $balance < 0 ? abs($balance) : 0;
            } else {
                // pasiva, modal, pendapatan → credit-normal
                $account->saldo_debit = $balance > 0 ? $balance : 0;
                $account->saldo_credit = $balance <= 0 ? abs($balance) : 0;
            }
        });

        return [
            'accounts' => $accounts,
            'total_debit' => $accounts->sum('saldo_debit'),
            'total_credit' => $accounts->sum('saldo_credit'),
        ];
    }

    /**
     * Get Balance Sheet data (Neraca)
     * Returns aktiva on one side, pasiva + modal + laba/rugi on the other
     *
     * @param array $filters ['period_id' => ?, 'date_from' => ?, 'date_to' => ?]
     * @return array
     */
    public function getBalanceSheet(array $filters = []): array
    {
        $query = $this->baseQuery();
        $this->applyFilters($query, $filters);

        $accounts = $query->select([
                'c_o_a_s.id as coa_id',
                'c_o_a_s.code as coa_code',
                'c_o_a_s.name as coa_name',
                'c_o_a_s.type as coa_type',
                DB::raw('SUM(journals.debit) as total_debit'),
                DB::raw('SUM(journals.credit) as total_credit'),
                DB::raw('(SUM(journals.debit) - SUM(journals.credit)) as balance'),
            ])
            ->groupBy('c_o_a_s.id', 'c_o_a_s.code', 'c_o_a_s.name', 'c_o_a_s.type')
            ->orderBy('c_o_a_s.code')
            ->get();

        // Calculate true balance per account type
        // Aktiva: debit - credit (positive = normal)
        // Pasiva, Modal: credit - debit (positive = normal)
        $accounts->each(function ($account) {
            if ($account->coa_type === 'aktiva') {
                $account->saldo = $account->total_debit - $account->total_credit;
            } elseif (in_array($account->coa_type, ['pasiva', 'modal'])) {
                $account->saldo = $account->total_credit - $account->total_debit;
            } elseif ($account->coa_type === 'pendapatan') {
                $account->saldo = $account->total_credit - $account->total_debit;
            } elseif ($account->coa_type === 'beban') {
                $account->saldo = $account->total_debit - $account->total_credit;
            } else {
                $account->saldo = $account->total_debit - $account->total_credit;
            }
        });

        $aktiva = $accounts->where('coa_type', 'aktiva');
        $pasiva = $accounts->where('coa_type', 'pasiva');
        $modal = $accounts->where('coa_type', 'modal');

        $totalAktiva = $aktiva->sum('saldo');
        $totalPasiva = $pasiva->sum('saldo');
        $totalModal = $modal->sum('saldo');

        // Calculate net income (laba/rugi) from income statement
        $incomeStatement = $this->getIncomeStatement($filters);
        $labaRugi = $incomeStatement['net_income'];

        return [
            'aktiva' => $aktiva->values(),
            'pasiva' => $pasiva->values(),
            'modal' => $modal->values(),
            'total_aktiva' => $totalAktiva,
            'total_pasiva' => $totalPasiva,
            'total_modal' => $totalModal,
            'laba_rugi' => $labaRugi,
            'total_pasiva_modal_laba' => $totalPasiva + $totalModal + $labaRugi,
            'is_balanced' => abs($totalAktiva - ($totalPasiva + $totalModal + $labaRugi)) < 0.01,
        ];
    }

    /**
     * Get Income Statement data (Laporan Laba Rugi)
     * Returns pendapatan and beban accounts with totals
     *
     * @param array $filters ['period_id' => ?, 'date_from' => ?, 'date_to' => ?]
     * @return array
     */
    public function getIncomeStatement(array $filters = []): array
    {
        $query = $this->baseQuery();
        $this->applyFilters($query, $filters);

        // Only get pendapatan and beban
        $query->whereIn('c_o_a_s.type', ['pendapatan', 'beban']);

        $accounts = $query->select([
                'c_o_a_s.id as coa_id',
                'c_o_a_s.code as coa_code',
                'c_o_a_s.name as coa_name',
                'c_o_a_s.type as coa_type',
                DB::raw('SUM(journals.debit) as total_debit'),
                DB::raw('SUM(journals.credit) as total_credit'),
            ])
            ->groupBy('c_o_a_s.id', 'c_o_a_s.code', 'c_o_a_s.name', 'c_o_a_s.type')
            ->orderBy('c_o_a_s.code')
            ->get();

        // Calculate proper balance per type
        $accounts->each(function ($account) {
            if ($account->coa_type === 'pendapatan') {
                // Pendapatan: credit - debit = positive means income
                $account->saldo = $account->total_credit - $account->total_debit;
            } else {
                // Beban: debit - credit = positive means expense
                $account->saldo = $account->total_debit - $account->total_credit;
            }
        });

        $pendapatan = $accounts->where('coa_type', 'pendapatan');
        $beban = $accounts->where('coa_type', 'beban');

        $totalPendapatan = $pendapatan->sum('saldo');
        $totalBeban = $beban->sum('saldo');
        $netIncome = $totalPendapatan - $totalBeban;

        return [
            'pendapatan' => $pendapatan->values(),
            'beban' => $beban->values(),
            'total_pendapatan' => $totalPendapatan,
            'total_beban' => $totalBeban,
            'net_income' => $netIncome,
            'is_profit' => $netIncome >= 0,
        ];
    }

    /**
     * Base query: posted journals joined with COA
     */
    private function baseQuery()
    {
        return Journal::query()
            ->join('journal_masters', 'journals.id_journal_master', '=', 'journal_masters.id')
            ->join('c_o_a_s', 'journals.id_coa', '=', 'c_o_a_s.id')
            ->where('journal_masters.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereNull('journal_masters.deleted_at')
            ->whereNull('c_o_a_s.deleted_at')
            ->where('c_o_a_s.is_active', true);
    }

    /**
     * Base query filtered by journal type
     */
    private function baseQueryByType(string $type)
    {
        return $this->baseQuery()->where('journal_masters.type', $type);
    }

    /**
     * Get Adjusted Trial Balance (Neraca Penyesuaian / Worksheet)
     * Shows: Original Trial Balance | Adjustments | Adjusted Trial Balance
     *
     * @param array $filters ['period_id' => ?, 'date_from' => ?, 'date_to' => ?]
     * @return array
     */
    public function getAdjustedTrialBalance(array $filters = []): array
    {
        // 1) Get all active leaf accounts so every row appears even if zero
        $allAccounts = COA::where('is_active', true)
            ->where('is_leaf_account', true)
            ->orderBy('code')
            ->get()
            ->keyBy('id');

        // 2) General journal totals (neraca saldo)
        $generalQuery = $this->baseQueryByType('general');
        $this->applyFilters($generalQuery, $filters);

        $generalData = $generalQuery->select([
                'c_o_a_s.id as coa_id',
                DB::raw('SUM(journals.debit) as total_debit'),
                DB::raw('SUM(journals.credit) as total_credit'),
            ])
            ->groupBy('c_o_a_s.id')
            ->get()
            ->keyBy('coa_id');

        // 3) Adjustment journal totals (penyesuaian)
        $adjustmentQuery = $this->baseQueryByType('adjustment');
        $this->applyFilters($adjustmentQuery, $filters);

        $adjustmentData = $adjustmentQuery->select([
                'c_o_a_s.id as coa_id',
                DB::raw('SUM(journals.debit) as total_debit'),
                DB::raw('SUM(journals.credit) as total_credit'),
            ])
            ->groupBy('c_o_a_s.id')
            ->get()
            ->keyBy('coa_id');

        // 4) Build worksheet rows
        $rows = collect();

        foreach ($allAccounts as $coaId => $coa) {
            $gen = $generalData->get($coaId);
            $adj = $adjustmentData->get($coaId);

            $genDebit = $gen ? (float) $gen->total_debit : 0;
            $genCredit = $gen ? (float) $gen->total_credit : 0;
            $adjDebit = $adj ? (float) $adj->total_debit : 0;
            $adjCredit = $adj ? (float) $adj->total_credit : 0;

            // Skip accounts with no activity at all
            if ($genDebit == 0 && $genCredit == 0 && $adjDebit == 0 && $adjCredit == 0) {
                continue;
            }

            $genBalance = $genDebit - $genCredit;
            $adjBalance = $adjDebit - $adjCredit;

            // Normalize to debit/credit columns based on account normal balance
            $isDebitNormal = in_array($coa->type, ['aktiva', 'beban']);

            // Neraca Saldo columns
            $nsDebit = 0;
            $nsCredit = 0;
            if ($isDebitNormal) {
                $nsDebit = $genBalance >= 0 ? $genBalance : 0;
                $nsCredit = $genBalance < 0 ? abs($genBalance) : 0;
            } else {
                $nsDebit = $genBalance > 0 ? $genBalance : 0;
                $nsCredit = $genBalance <= 0 ? abs($genBalance) : 0;
            }

            // Penyesuaian columns (raw debit/credit)
            $penyDebit = $adjDebit;
            $penyCredit = $adjCredit;

            // Neraca Saldo Disesuaikan
            $combinedBalance = $genBalance + $adjBalance;
            $nsdDebit = 0;
            $nsdCredit = 0;
            if ($isDebitNormal) {
                $nsdDebit = $combinedBalance >= 0 ? $combinedBalance : 0;
                $nsdCredit = $combinedBalance < 0 ? abs($combinedBalance) : 0;
            } else {
                $nsdDebit = $combinedBalance > 0 ? $combinedBalance : 0;
                $nsdCredit = $combinedBalance <= 0 ? abs($combinedBalance) : 0;
            }

            $rows->push((object) [
                'coa_id' => $coaId,
                'coa_code' => $coa->code,
                'coa_name' => $coa->name,
                'coa_type' => $coa->type,
                'ns_debit' => $nsDebit,
                'ns_credit' => $nsCredit,
                'adj_debit' => $penyDebit,
                'adj_credit' => $penyCredit,
                'nsd_debit' => $nsdDebit,
                'nsd_credit' => $nsdCredit,
            ]);
        }

        return [
            'accounts' => $rows,
            'total_ns_debit' => $rows->sum('ns_debit'),
            'total_ns_credit' => $rows->sum('ns_credit'),
            'total_adj_debit' => $rows->sum('adj_debit'),
            'total_adj_credit' => $rows->sum('adj_credit'),
            'total_nsd_debit' => $rows->sum('nsd_debit'),
            'total_nsd_credit' => $rows->sum('nsd_credit'),
            'ns_balanced' => abs($rows->sum('ns_debit') - $rows->sum('ns_credit')) < 0.01,
            'adj_balanced' => abs($rows->sum('adj_debit') - $rows->sum('adj_credit')) < 0.01,
            'nsd_balanced' => abs($rows->sum('nsd_debit') - $rows->sum('nsd_credit')) < 0.01,
        ];
    }

    /**
     * Apply common filters to query
     */
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['period_id'])) {
            $query->where('journal_masters.id_period', $filters['period_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('journal_masters.journal_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('journal_masters.journal_date', '<=', $filters['date_to']);
        }
    }
}
