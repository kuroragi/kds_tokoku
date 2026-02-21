<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. Bank Mutations (imported from CSV/Excel) ───
        Schema::create('bank_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained();
            $table->foreignId('bank_account_id')->constrained();
            $table->date('transaction_date');
            $table->string('description', 500);
            $table->string('reference_no', 100)->nullable();
            $table->decimal('debit', 18, 2)->default(0);   // uang masuk
            $table->decimal('credit', 18, 2)->default(0);  // uang keluar
            $table->decimal('balance', 18, 2)->default(0);  // saldo per baris
            $table->string('status', 20)->default('unmatched'); // unmatched, matched, ignored
            $table->foreignId('matched_journal_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->foreignId('matched_fund_transfer_id')->nullable()->constrained('fund_transfers')->nullOnDelete();
            $table->string('import_batch', 50)->nullable();  // batch grouping
            $table->text('raw_data')->nullable();  // original CSV row JSON
            $table->timestamps();
            $table->softDeletes();

            $table->index(['bank_account_id', 'transaction_date']);
            $table->index(['status']);
            $table->index(['import_batch']);
        });

        // ─── 2. Bank Reconciliations (header per session) ───
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained();
            $table->foreignId('bank_account_id')->constrained();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('bank_statement_balance', 18, 2)->default(0);
            $table->decimal('system_balance', 18, 2)->default(0);
            $table->decimal('difference', 18, 2)->default(0);
            $table->unsignedInteger('total_mutations')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('unmatched_count')->default(0);
            $table->string('status', 20)->default('draft'); // draft, completed
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['bank_account_id', 'start_date', 'end_date']);
        });

        // ─── 3. Reconciliation Items (detail matching) ───
        Schema::create('bank_reconciliation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_reconciliation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_mutation_id')->constrained();
            $table->string('match_type', 30)->default('unmatched');
            // match_type: auto_matched, manual_matched, unmatched, ignored, adjustment
            $table->foreignId('matched_journal_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->foreignId('matched_fund_transfer_id')->nullable()->constrained('fund_transfers')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['bank_reconciliation_id', 'match_type'], 'bri_recon_match_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_items');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_mutations');
    }
};
