<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== ADD piutang_karyawan COA MAPPING KEY ====================
        // (handled in BusinessUnitCoaMapping::getAccountKeyDefinitions() code update)

        // ==================== ADD pph21_rate TO PAYROLL ENTRIES ====================
        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->decimal('pph21_rate', 5, 2)->default(0)
                ->after('pph21_amount')
                ->comment('TER rate used for calculation');
        });

        // ==================== ADD pinjaman CATEGORY TO SALARY COMPONENTS ====================
        // (handled in SalaryComponent model CATEGORIES constant update)

        // ==================== EMPLOYEE LOANS (KASBON / PINJAMAN) ====================
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('loan_number', 50);
            $table->string('description')->nullable();
            $table->bigInteger('loan_amount');
            $table->integer('installment_count')->comment('Jumlah cicilan (bulan)');
            $table->bigInteger('installment_amount')->comment('Cicilan per bulan');
            $table->date('disbursed_date')->comment('Tanggal pencairan');
            $table->date('start_deduction_date')->nullable()
                ->comment('Mulai potong dari payroll bulan ini');
            $table->foreignId('payment_coa_id')->constrained('c_o_a_s')
                ->comment('Akun kas/bank yang digunakan untuk pencairan');
            $table->foreignId('journal_master_id')->nullable()
                ->constrained('journal_masters')->nullOnDelete()
                ->comment('Jurnal pencairan pinjaman');
            $table->bigInteger('total_paid')->default(0);
            $table->bigInteger('remaining_amount')->default(0);
            $table->enum('status', ['active', 'paid_off', 'void'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unique(['business_unit_id', 'loan_number']);
        });

        // ==================== EMPLOYEE LOAN PAYMENTS ====================
        Schema::create('employee_loan_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->nullable()
                ->constrained()->nullOnDelete()
                ->comment('Null jika pembayaran manual (di luar payroll)');
            $table->foreignId('payroll_entry_detail_id')->nullable()
                ->constrained()->nullOnDelete()
                ->comment('Link ke detail potongan payroll');
            $table->date('payment_date');
            $table->bigInteger('amount');
            $table->string('reference', 100)->nullable();
            $table->foreignId('journal_master_id')->nullable()
                ->constrained('journal_masters')->nullOnDelete()
                ->comment('Jurnal untuk pembayaran manual');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loan_payments');
        Schema::dropIfExists('employee_loans');

        Schema::table('payroll_entries', function (Blueprint $table) {
            $table->dropColumn('pph21_rate');
        });
    }
};
