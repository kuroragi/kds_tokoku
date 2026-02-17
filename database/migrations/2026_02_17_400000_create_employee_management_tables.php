<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ==================== ENHANCE EMPLOYEES ====================
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('position_id')
                ->constrained('users')->nullOnDelete();
            $table->bigInteger('base_salary')->nullable()->after('join_date');
            $table->string('bank_name', 50)->nullable()->after('base_salary');
            $table->string('bank_account_number', 30)->nullable()->after('bank_name');
            $table->string('bank_account_name', 100)->nullable()->after('bank_account_number');
            $table->string('npwp', 30)->nullable()->after('bank_account_name');
            $table->string('ptkp_status', 10)->nullable()->after('npwp')
                ->comment('TK/0, TK/1, TK/2, TK/3, K/0, K/1, K/2, K/3');
        });

        // ==================== SALARY COMPONENTS ====================
        Schema::create('salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['earning', 'deduction', 'benefit'])
                ->comment('earning=penghasilan, deduction=potongan, benefit=beban perusahaan');
            $table->string('category', 30)
                ->comment('gaji_pokok, tunjangan_tetap, tunjangan_tidak_tetap, bpjs, lembur, potongan, pph21');
            $table->enum('apply_method', ['auto', 'template', 'manual'])
                ->comment('auto=otomatis semua karyawan, template=per jabatan/karyawan, manual=input saat payroll');
            $table->enum('calculation_type', ['fixed', 'percentage', 'employee_field'])
                ->default('fixed')
                ->comment('fixed=nominal tetap, percentage=persentase, employee_field=dari field karyawan');
            $table->string('employee_field_name', 50)->nullable()
                ->comment('Nama field di Employee model, misal: base_salary');
            $table->string('setting_key', 50)->nullable()
                ->comment('Key di payroll_settings untuk rate persentase (BPJS)');
            $table->string('percentage_base', 30)->nullable()->default('gaji_pokok')
                ->comment('Basis perhitungan persentase');
            $table->bigInteger('default_amount')->nullable()
                ->comment('Nilai default (nominal atau rate*100 untuk persentase)');
            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unique(['business_unit_id', 'code']);
        });

        // ==================== POSITION SALARY TEMPLATE ====================
        Schema::create('position_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->timestamps();
            $table->unique(['position_id', 'salary_component_id'], 'pos_sal_comp_unique');
        });

        // ==================== EMPLOYEE SALARY ASSIGNMENT ====================
        Schema::create('employee_salary_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount');
            $table->timestamps();
            $table->unique(['employee_id', 'salary_component_id'], 'emp_sal_comp_unique');
        });

        // ==================== PAYROLL SETTINGS (BPJS CONFIG) ====================
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('value');
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('type', 20)->default('percentage')
                ->comment('percentage, amount, boolean');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['business_unit_id', 'key']);
        });

        // ==================== PAYROLL PERIODS ====================
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('period_id')->nullable()->constrained()->nullOnDelete();
            $table->tinyInteger('month');
            $table->smallInteger('year');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'void'])
                ->default('draft');
            $table->bigInteger('total_earnings')->default(0);
            $table->bigInteger('total_benefits')->default(0);
            $table->bigInteger('total_deductions')->default(0);
            $table->bigInteger('total_net')->default(0);
            $table->bigInteger('total_tax')->default(0);
            $table->foreignId('payment_coa_id')->nullable()
                ->constrained('c_o_a_s')->nullOnDelete();
            $table->foreignId('journal_master_id')->nullable()
                ->constrained('journal_masters')->nullOnDelete();
            $table->date('paid_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unique(['business_unit_id', 'month', 'year']);
        });

        // ==================== PAYROLL ENTRIES (PER EMPLOYEE PER PERIOD) ====================
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('base_salary')->default(0);
            $table->bigInteger('total_earnings')->default(0);
            $table->bigInteger('total_benefits')->default(0);
            $table->bigInteger('total_deductions')->default(0);
            $table->bigInteger('pph21_amount')->default(0);
            $table->bigInteger('gross_salary')->default(0)
                ->comment('total_earnings + total_benefits');
            $table->bigInteger('net_salary')->default(0)
                ->comment('total_earnings - total_deductions');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->unique(['payroll_period_id', 'employee_id'], 'payroll_emp_unique');
        });

        // ==================== PAYROLL ENTRY DETAILS (COMPONENT BREAKDOWN) ====================
        Schema::create('payroll_entry_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('salary_component_id')->nullable()
                ->constrained()->nullOnDelete();
            $table->string('component_name');
            $table->enum('type', ['earning', 'deduction', 'benefit']);
            $table->string('category', 30);
            $table->bigInteger('amount')->default(0);
            $table->boolean('is_auto_calculated')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ==================== PPH21 TER RATES (FOUNDATION) ====================
        Schema::create('pph21_ter_rates', function (Blueprint $table) {
            $table->id();
            $table->char('category', 1)->comment('A, B, or C');
            $table->bigInteger('min_income');
            $table->bigInteger('max_income')->nullable()->comment('null = unlimited');
            $table->decimal('rate', 5, 2)->comment('percentage rate');
            $table->timestamps();
            $table->index(['category', 'min_income']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pph21_ter_rates');
        Schema::dropIfExists('payroll_entry_details');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_settings');
        Schema::dropIfExists('employee_salary_components');
        Schema::dropIfExists('position_salary_components');
        Schema::dropIfExists('salary_components');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'base_salary', 'bank_name', 'bank_account_number',
                'bank_account_name', 'npwp', 'ptkp_status',
            ]);
        });
    }
};
