<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop views first for compatibility with both MySQL and SQLite
        $this->down();

        // 1. View untuk Rekap Journal per Periode
        DB::statement("
            CREATE VIEW view_journal_recap AS
            SELECT 
                jm.id_period,
                p.code as period_code,
                p.name as period_name,
                p.year,
                p.month,
                COUNT(jm.id) as total_journals,
                SUM(jm.total_debit) as total_debit,
                SUM(jm.total_credit) as total_credit,
                COUNT(CASE WHEN jm.status = 'posted' THEN 1 END) as posted_journals,
                COUNT(CASE WHEN jm.status = 'draft' THEN 1 END) as draft_journals
            FROM journal_masters jm
            LEFT JOIN periods p ON jm.id_period = p.id
            WHERE jm.deleted_at IS NULL
            GROUP BY jm.id_period, p.code, p.name, p.year, p.month
        ");

        // 2. View untuk Detail Journal dengan COA
        DB::statement("
            CREATE VIEW view_journal_details AS
            SELECT 
                j.id,
                jm.id as journal_master_id,
                jm.journal_no,
                jm.journal_date,
                jm.id_period,
                p.code as period_code,
                p.name as period_name,
                c.code as coa_code,
                c.name as coa_name,
                c.type as coa_type,
                j.description,
                j.debit,
                j.credit,
                CASE WHEN j.debit > 0 THEN 'debit' ELSE 'credit' END as entry_type,
                CASE WHEN j.debit > 0 THEN j.debit ELSE j.credit END as amount,
                j.sequence,
                jm.status as journal_status
            FROM journals j
            INNER JOIN journal_masters jm ON j.id_journal_master = jm.id
            INNER JOIN c_o_a_s c ON j.id_coa = c.id
            LEFT JOIN periods p ON jm.id_period = p.id
            WHERE j.deleted_at IS NULL 
                AND jm.deleted_at IS NULL 
                AND c.deleted_at IS NULL
            ORDER BY jm.journal_date, jm.journal_no, j.sequence
        ");

        // 3. View untuk Buku Besar (General Ledger)
        DB::statement("
            CREATE VIEW view_general_ledger AS
            SELECT 
                c.id as coa_id,
                c.code as coa_code,
                c.name as coa_name,
                c.type as coa_type,
                jm.id_period,
                p.code as period_code,
                p.name as period_name,
                p.year,
                p.month,
                COUNT(j.id) as total_transactions,
                SUM(j.debit) as total_debit,
                SUM(j.credit) as total_credit,
                (SUM(j.debit) - SUM(j.credit)) as balance
            FROM c_o_a_s c
            LEFT JOIN journals j ON c.id = j.id_coa AND j.deleted_at IS NULL
            LEFT JOIN journal_masters jm ON j.id_journal_master = jm.id AND jm.deleted_at IS NULL AND jm.status = 'posted'
            LEFT JOIN periods p ON jm.id_period = p.id AND p.deleted_at IS NULL
            WHERE c.deleted_at IS NULL AND c.is_active = 1
            GROUP BY c.id, c.code, c.name, c.type, jm.id_period, p.code, p.name, p.year, p.month
            HAVING total_transactions > 0 OR c.id IS NOT NULL
        ");

        // 4. View untuk Neraca Saldo (Trial Balance) per Periode
        DB::statement("
            CREATE VIEW view_trial_balance AS
            SELECT 
                jm.id_period,
                p.code as period_code,
                p.name as period_name,
                p.year,
                p.month,
                c.id as coa_id,
                c.code as coa_code,
                c.name as coa_name,
                c.type as coa_type,
                SUM(j.debit) as total_debit,
                SUM(j.credit) as total_credit,
                (SUM(j.debit) - SUM(j.credit)) as balance,
                CASE 
                    WHEN c.type IN ('aktiva', 'beban') AND (SUM(j.debit) - SUM(j.credit)) > 0 THEN SUM(j.debit) - SUM(j.credit)
                    WHEN c.type IN ('pasiva', 'modal', 'pendapatan') AND (SUM(j.debit) - SUM(j.credit)) < 0 THEN ABS(SUM(j.debit) - SUM(j.credit))
                    ELSE 0
                END as normal_balance
            FROM journals j
            INNER JOIN journal_masters jm ON j.id_journal_master = jm.id
            INNER JOIN c_o_a_s c ON j.id_coa = c.id
            INNER JOIN periods p ON jm.id_period = p.id
            WHERE j.deleted_at IS NULL 
                AND jm.deleted_at IS NULL 
                AND c.deleted_at IS NULL
                AND p.deleted_at IS NULL
                AND jm.status = 'posted'
                AND c.is_active = 1
            GROUP BY jm.id_period, p.code, p.name, p.year, p.month, c.id, c.code, c.name, c.type
            HAVING SUM(j.debit) != 0 OR SUM(j.credit) != 0
            ORDER BY c.type, c.code
        ");

        // 5. View untuk Laporan Laba Rugi per Periode
        DB::statement("
            CREATE VIEW view_income_statement AS
            SELECT 
                jm.id_period,
                p.code as period_code,
                p.name as period_name,
                p.year,
                p.month,
                c.type as account_type,
                SUM(CASE WHEN c.type = 'pendapatan' THEN j.credit - j.debit ELSE 0 END) as total_pendapatan,
                SUM(CASE WHEN c.type = 'beban' THEN j.debit - j.credit ELSE 0 END) as total_beban,
                (SUM(CASE WHEN c.type = 'pendapatan' THEN j.credit - j.debit ELSE 0 END) - 
                 SUM(CASE WHEN c.type = 'beban' THEN j.debit - j.credit ELSE 0 END)) as net_income
            FROM journals j
            INNER JOIN journal_masters jm ON j.id_journal_master = jm.id
            INNER JOIN c_o_a_s c ON j.id_coa = c.id
            INNER JOIN periods p ON jm.id_period = p.id
            WHERE j.deleted_at IS NULL 
                AND jm.deleted_at IS NULL 
                AND c.deleted_at IS NULL
                AND p.deleted_at IS NULL
                AND jm.status = 'posted'
                AND c.type IN ('pendapatan', 'beban')
            GROUP BY jm.id_period, p.code, p.name, p.year, p.month, c.type
        ");

        // 6. View untuk Laporan Neraca (Balance Sheet) per Periode
        DB::statement("
            CREATE VIEW view_balance_sheet AS
            SELECT 
                jm.id_period,
                p.code as period_code,
                p.name as period_name,
                p.year,
                p.month,
                c.type as account_type,
                SUM(CASE WHEN c.type = 'aktiva' THEN j.debit - j.credit ELSE 0 END) as total_aktiva,
                SUM(CASE WHEN c.type = 'pasiva' THEN j.credit - j.debit ELSE 0 END) as total_pasiva,
                SUM(CASE WHEN c.type = 'modal' THEN j.credit - j.debit ELSE 0 END) as total_modal
            FROM journals j
            INNER JOIN journal_masters jm ON j.id_journal_master = jm.id
            INNER JOIN c_o_a_s c ON j.id_coa = c.id
            INNER JOIN periods p ON jm.id_period = p.id
            WHERE j.deleted_at IS NULL 
                AND jm.deleted_at IS NULL 
                AND c.deleted_at IS NULL
                AND p.deleted_at IS NULL
                AND jm.status = 'posted'
                AND c.type IN ('aktiva', 'pasiva', 'modal')
            GROUP BY jm.id_period, p.code, p.name, p.year, p.month, c.type
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS view_balance_sheet');
        DB::statement('DROP VIEW IF EXISTS view_income_statement');
        DB::statement('DROP VIEW IF EXISTS view_trial_balance');
        DB::statement('DROP VIEW IF EXISTS view_general_ledger');
        DB::statement('DROP VIEW IF EXISTS view_journal_details');
        DB::statement('DROP VIEW IF EXISTS view_journal_recap');
    }
};
