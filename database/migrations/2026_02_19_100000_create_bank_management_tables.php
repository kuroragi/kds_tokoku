<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master Bank
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('swift_code', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        // Cash Accounts (Kas per unit usaha)
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('name')->default('Kas Utama');
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        // Bank Accounts (Rekening bank milik usaha)
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('bank_id')->constrained('banks')->restrictOnDelete();
            $table->string('account_number', 30);
            $table->string('account_name');
            $table->text('description')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
            $table->unique(['business_unit_id', 'bank_id', 'account_number']);
        });

        // Bank Fee Matrix (Biaya admin antar-bank)
        Schema::create('bank_fee_matrix', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_bank_id')->constrained('banks')->cascadeOnDelete();
            $table->foreignId('destination_bank_id')->constrained('banks')->cascadeOnDelete();
            $table->string('transfer_type', 30)->default('online'); // online, bi-fast, rtgs, sknbi
            $table->decimal('fee', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->blameable();
            $table->unique(['source_bank_id', 'destination_bank_id', 'transfer_type'], 'fee_matrix_unique');
        });

        // Fund Transfers (Perpindahan dana)
        Schema::create('fund_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->enum('source_type', ['cash', 'bank']);
            $table->foreignId('source_bank_account_id')->nullable()->constrained('bank_accounts')->restrictOnDelete();
            $table->enum('destination_type', ['cash', 'bank']);
            $table->foreignId('destination_bank_account_id')->nullable()->constrained('bank_accounts')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('admin_fee', 15, 2)->default(0);
            $table->date('transfer_date');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fund_transfers');
        Schema::dropIfExists('bank_fee_matrix');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('cash_accounts');
        Schema::dropIfExists('banks');
    }
};
