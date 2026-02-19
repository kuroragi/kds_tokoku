<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Purchase Orders ───
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->string('po_number', 50)->unique();
            $table->date('po_date');
            $table->date('expected_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->enum('status', ['draft', 'confirmed', 'partial_received', 'received', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->restrictOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('received_quantity', 15, 2)->default(0);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->blameable();
        });

        // ─── Purchases (Direct & from PO) ───
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->date('purchase_date');
            $table->date('due_date')->nullable();
            $table->text('notes')->nullable();

            // Amounts
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            // Payment tracking
            $table->enum('payment_type', ['cash', 'credit', 'partial', 'down_payment'])->default('cash');
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('down_payment_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');

            // Status
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');

            // Journal reference
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        Schema::create('purchase_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->restrictOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->blameable();
        });

        // ─── Purchase Payments ───
        Schema::create('purchase_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained('purchases')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'e-wallet', 'other'])->default('cash');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        // ─── Stock Opname ───
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('opname_number', 50)->unique();
            $table->date('opname_date');
            $table->string('pic_name')->nullable(); // Penanggung Jawab
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->restrictOnDelete();
            $table->decimal('system_qty', 15, 2);
            $table->decimal('actual_qty', 15, 2);
            $table->decimal('difference', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->blameable();
        });

        // ─── Saldo Opname ───
        Schema::create('saldo_opnames', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('opname_number', 50)->unique();
            $table->date('opname_date');
            $table->string('pic_name')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'approved', 'cancelled'])->default('draft');
            $table->foreignId('journal_master_id')->nullable()->constrained('journal_masters')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        Schema::create('saldo_opname_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saldo_opname_id')->constrained('saldo_opnames')->cascadeOnDelete();
            $table->foreignId('saldo_provider_id')->constrained('saldo_providers')->restrictOnDelete();
            $table->decimal('system_balance', 15, 2);
            $table->decimal('actual_balance', 15, 2);
            $table->decimal('difference', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->blameable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_opname_details');
        Schema::dropIfExists('saldo_opnames');
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opnames');
        Schema::dropIfExists('purchase_payments');
        Schema::dropIfExists('purchase_items');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
    }
};
