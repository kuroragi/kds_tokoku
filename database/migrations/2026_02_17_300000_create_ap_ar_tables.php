<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add is_net_pph23 to vendors table
        Schema::table('vendors', function (Blueprint $table) {
            $table->boolean('is_net_pph23')->default(false)->after('pph23_rate');
        });

        // Payables (Hutang Usaha)
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units');
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('description')->nullable();
            $table->foreignId('debit_coa_id')->nullable()->constrained('c_o_a_s');
            $table->bigInteger('input_amount'); // what user originally entered
            $table->boolean('is_net_basis')->default(false);
            $table->bigInteger('dpp')->default(0); // DPP / gross / taxable base
            $table->decimal('pph23_rate', 5, 2)->default(0);
            $table->bigInteger('pph23_amount')->default(0);
            $table->bigInteger('amount_due')->default(0); // what vendor should receive
            $table->bigInteger('paid_amount')->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'void'])->default('unpaid');
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'invoice_number']);
        });

        // Receivables (Piutang Usaha)
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units');
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('invoice_number', 50);
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('description')->nullable();
            $table->foreignId('credit_coa_id')->nullable()->constrained('c_o_a_s');
            $table->bigInteger('amount');
            $table->bigInteger('paid_amount')->default(0);
            $table->enum('status', ['unpaid', 'partial', 'paid', 'void'])->default('unpaid');
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'invoice_number']);
        });

        // Payable Payments (Pembayaran Hutang)
        Schema::create('payable_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payable_id')->constrained('payables');
            $table->date('payment_date');
            $table->bigInteger('amount');
            $table->foreignId('payment_coa_id')->constrained('c_o_a_s');
            $table->string('reference', 100)->nullable();
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        // Receivable Payments (Penerimaan Piutang)
        Schema::create('receivable_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained('receivables');
            $table->date('payment_date');
            $table->bigInteger('amount');
            $table->foreignId('payment_coa_id')->constrained('c_o_a_s');
            $table->string('reference', 100)->nullable();
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receivable_payments');
        Schema::dropIfExists('payable_payments');
        Schema::dropIfExists('receivables');
        Schema::dropIfExists('payables');

        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('is_net_pph23');
        });
    }
};
