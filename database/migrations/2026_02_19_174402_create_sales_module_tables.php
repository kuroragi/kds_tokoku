<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Sales ──
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->string('sale_type', 20)->default('goods')->comment('goods,saldo,service,mix');
            $table->date('sale_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->string('payment_type', 30)->default('cash')->comment('cash,credit,partial,down_payment,prepaid_deduction');
            $table->string('payment_source', 50)->nullable()->comment('kas_utama,kas_kecil,bank_utama');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('down_payment_amount', 15, 2)->default(0);
            $table->decimal('prepaid_deduction_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->string('payment_status', 20)->default('unpaid')->comment('unpaid,partial,paid');
            $table->string('status', 20)->default('draft')->comment('draft,confirmed,completed,cancelled');
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        // ── Sale Items ──
        Schema::create('sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 20)->default('goods')->comment('goods,saldo,service');
            $table->string('description')->nullable();
            $table->foreignId('stock_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('saldo_provider_id')->nullable();
            $table->decimal('quantity', 15, 2)->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('saldo_provider_id')->references('id')->on('saldo_providers')->nullOnDelete();
        });

        // ── Sale Payments ──
        Schema::create('sale_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_method', 30)->default('cash')->comment('cash,bank_transfer,giro,e_wallet,other');
            $table->string('payment_source', 50)->nullable()->comment('kas_utama,kas_kecil,bank_utama');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });

        // ── Add prepaid fields to existing tables ──
        Schema::table('purchases', function (Blueprint $table) {
            $table->decimal('prepaid_deduction_amount', 15, 2)->default(0)->after('down_payment_amount');
        });

        // ── Add min_balance to saldo_providers for warehouse monitoring ──
        if (!Schema::hasColumn('saldo_providers', 'min_balance')) {
            Schema::table('saldo_providers', function (Blueprint $table) {
                $table->decimal('min_balance', 15, 2)->default(0)->after('current_balance');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('prepaid_deduction_amount');
        });

        if (Schema::hasColumn('saldo_providers', 'min_balance')) {
            Schema::table('saldo_providers', function (Blueprint $table) {
                $table->dropColumn('min_balance');
            });
        }
    }
};
