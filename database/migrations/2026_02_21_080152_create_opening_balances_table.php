<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Header table: one opening balance set per business unit per period
        Schema::create('opening_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->constrained('periods')->restrictOnDelete();
            $table->date('balance_date');
            $table->text('description')->nullable();
            $table->decimal('total_debit', 20, 2)->default(0);
            $table->decimal('total_credit', 20, 2)->default(0);
            $table->enum('status', ['draft', 'posted'])->default('draft');
            $table->foreignId('journal_master_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['business_unit_id', 'period_id']);
        });

        // Detail: each COA entry in the opening balance
        Schema::create('opening_balance_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opening_balance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coa_id')->constrained('c_o_a_s')->restrictOnDelete();
            $table->string('coa_code', 20);
            $table->string('coa_name');
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('credit', 20, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->unique(['opening_balance_id', 'coa_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_balance_entries');
        Schema::dropIfExists('opening_balances');
    }
};
