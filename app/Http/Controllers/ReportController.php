<?php

namespace App\Http\Controllers;

use App\Models\COA;
use App\Models\Journal;
use App\Models\Period;
use App\Services\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    protected FinancialReportService $reportService;

    public function __construct(FinancialReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Build filter array from request
     */
    private function getFilters(Request $request): array
    {
        return array_filter([
            'period_id' => $request->input('period_id') ?: null,
            'date_from' => $request->input('date_from') ?: null,
            'date_to' => $request->input('date_to') ?: null,
        ]);
    }

    /**
     * Build filter description for header
     */
    private function getFilterDescription(Request $request): string
    {
        if ($request->input('period_id')) {
            $period = Period::find($request->input('period_id'));
            return $period ? "Periode: {$period->period_name}" : '';
        }

        $parts = [];
        if ($request->input('date_from')) {
            $parts[] = 'Dari: ' . \Carbon\Carbon::parse($request->input('date_from'))->format('d/m/Y');
        }
        if ($request->input('date_to')) {
            $parts[] = 'Sampai: ' . \Carbon\Carbon::parse($request->input('date_to'))->format('d/m/Y');
        }

        return implode(' — ', $parts);
    }

    /**
     * Download Trial Balance PDF
     */
    public function trialBalance(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = $this->getFilters($request);

        if (empty($filters)) {
            return back()->with('error', 'Pilih periode atau range tanggal terlebih dahulu.');
        }

        $data = $this->reportService->getTrialBalance($filters);

        $pdf = Pdf::loadView('pdf.trial-balance', [
            'data' => $data,
            'filterDescription' => $this->getFilterDescription($request),
            'title' => 'Neraca Saldo (Trial Balance)',
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('neraca-saldo-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Download Balance Sheet PDF
     */
    public function balanceSheet(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = $this->getFilters($request);

        if (empty($filters)) {
            return back()->with('error', 'Pilih periode atau range tanggal terlebih dahulu.');
        }

        $data = $this->reportService->getBalanceSheet($filters);

        $pdf = Pdf::loadView('pdf.balance-sheet', [
            'data' => $data,
            'filterDescription' => $this->getFilterDescription($request),
            'title' => 'Neraca (Balance Sheet)',
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('neraca-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Download Income Statement PDF
     */
    public function incomeStatement(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = $this->getFilters($request);

        if (empty($filters)) {
            return back()->with('error', 'Pilih periode atau range tanggal terlebih dahulu.');
        }

        $data = $this->reportService->getIncomeStatement($filters);

        $pdf = Pdf::loadView('pdf.income-statement', [
            'data' => $data,
            'filterDescription' => $this->getFilterDescription($request),
            'title' => 'Laporan Laba Rugi (Income Statement)',
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('laba-rugi-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Download Adjusted Trial Balance PDF
     */
    public function adjustedTrialBalance(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = $this->getFilters($request);

        if (empty($filters)) {
            return back()->with('error', 'Pilih periode atau range tanggal terlebih dahulu.');
        }

        $data = $this->reportService->getAdjustedTrialBalance($filters);

        $pdf = Pdf::loadView('pdf.adjusted-trial-balance', [
            'data' => $data,
            'filterDescription' => $this->getFilterDescription($request),
            'title' => 'Neraca Penyesuaian (Adjusted Trial Balance)',
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('neraca-penyesuaian-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Download General Ledger PDF
     */
    public function generalLedger(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'coa_id' => 'nullable|exists:c_o_a_s,id',
            'coa_type' => 'nullable|in:aktiva,pasiva,modal,pendapatan,beban',
        ]);

        $filters = $this->getFilters($request);

        // Build general ledger data
        $query = Journal::query()
            ->join('journal_masters', 'journals.id_journal_master', '=', 'journal_masters.id')
            ->join('c_o_a_s', 'journals.id_coa', '=', 'c_o_a_s.id')
            ->leftJoin('periods', 'journal_masters.id_period', '=', 'periods.id')
            ->where('journal_masters.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereNull('journal_masters.deleted_at')
            ->whereNull('c_o_a_s.deleted_at')
            ->where('c_o_a_s.is_active', true);

        if (!empty($filters['period_id'])) {
            $query->where('journal_masters.id_period', $filters['period_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('journal_masters.journal_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('journal_masters.journal_date', '<=', $filters['date_to']);
        }
        if ($request->input('coa_id')) {
            $query->where('journals.id_coa', $request->input('coa_id'));
        }
        if ($request->input('coa_type')) {
            $query->where('c_o_a_s.type', $request->input('coa_type'));
        }

        $summaryData = $query->select([
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

        $pdf = Pdf::loadView('pdf.general-ledger', [
            'summaryData' => $summaryData,
            'grandTotalDebit' => $summaryData->sum('total_debit'),
            'grandTotalCredit' => $summaryData->sum('total_credit'),
            'filterDescription' => $this->getFilterDescription($request),
            'title' => 'Buku Besar (General Ledger)',
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download('buku-besar-' . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Download General Ledger Detail PDF for a specific COA
     */
    public function generalLedgerDetail(Request $request, COA $coa)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $query = Journal::query()
            ->join('journal_masters', 'journals.id_journal_master', '=', 'journal_masters.id')
            ->leftJoin('periods', 'journal_masters.id_period', '=', 'periods.id')
            ->where('journals.id_coa', $coa->id)
            ->where('journal_masters.status', 'posted')
            ->whereNull('journals.deleted_at')
            ->whereNull('journal_masters.deleted_at');

        $filters = $this->getFilters($request);
        if (!empty($filters['period_id'])) {
            $query->where('journal_masters.id_period', $filters['period_id']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('journal_masters.journal_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('journal_masters.journal_date', '<=', $filters['date_to']);
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

        // Running balance
        $runningBalance = 0;
        foreach ($entries as $entry) {
            $runningBalance += ($entry->debit - $entry->credit);
            $entry->running_balance = $runningBalance;
        }

        $pdf = Pdf::loadView('pdf.general-ledger-detail', [
            'coa' => $coa,
            'entries' => $entries,
            'totalDebit' => $entries->sum('debit'),
            'totalCredit' => $entries->sum('credit'),
            'finalBalance' => $runningBalance,
            'filterDescription' => $this->getFilterDescription($request),
            'title' => "Buku Besar — {$coa->code} {$coa->name}",
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("buku-besar-{$coa->code}-" . now()->format('Ymd_His') . '.pdf');
    }

    /**
     * Download Final Balance Sheet PDF (Neraca Keuangan Final)
     * Combines Balance Sheet + Income Statement into one comprehensive report
     */
    public function finalBalanceSheet(Request $request)
    {
        $request->validate([
            'period_id' => 'nullable|exists:periods,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = $this->getFilters($request);

        if (empty($filters)) {
            return back()->with('error', 'Pilih periode atau range tanggal terlebih dahulu.');
        }

        $balanceSheet = $this->reportService->getBalanceSheet($filters);
        $incomeStatement = $this->reportService->getIncomeStatement($filters);

        $pdf = Pdf::loadView('pdf.final-balance-sheet', [
            'balanceSheet' => $balanceSheet,
            'incomeStatement' => $incomeStatement,
            'filterDescription' => $this->getFilterDescription($request),
            'title' => 'Neraca Keuangan Final',
            'printDate' => now()->format('d/m/Y H:i'),
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download('neraca-keuangan-final-' . now()->format('Ymd_His') . '.pdf');
    }
}
