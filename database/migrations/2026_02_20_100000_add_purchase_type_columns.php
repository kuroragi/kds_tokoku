<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add purchase_type to purchases table
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('purchase_type', ['goods', 'saldo', 'service', 'mix'])
                  ->default('goods')
                  ->after('invoice_number');
        });

        // Add item_type, description to purchase_items + make stock_id nullable
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->enum('item_type', ['goods', 'saldo', 'service'])
                  ->default('goods')
                  ->after('purchase_id');
            $table->text('description')->nullable()->after('item_type');
            $table->unsignedBigInteger('saldo_provider_id')->nullable()->after('stock_id');

            $table->foreign('saldo_provider_id')
                  ->references('id')
                  ->on('saldo_providers')
                  ->nullOnDelete();
        });

        // Make stock_id nullable on purchase_items (for service/saldo items)
        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->unsignedBigInteger('stock_id')->nullable()->change();
            $table->foreign('stock_id')
                  ->references('id')
                  ->on('stocks')
                  ->restrictOnDelete();
        });

        // Also update payment methods enum to include giro
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'bank_transfer', 'giro', 'e_wallet', 'other'])
                  ->default('cash')
                  ->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'bank_transfer', 'e-wallet', 'other'])
                  ->default('cash')
                  ->after('payment_date');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropForeign(['stock_id']);
            $table->unsignedBigInteger('stock_id')->nullable(false)->change();
            $table->foreign('stock_id')
                  ->references('id')
                  ->on('stocks')
                  ->restrictOnDelete();

            $table->dropForeign(['saldo_provider_id']);
            $table->dropColumn(['item_type', 'description', 'saldo_provider_id']);
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('purchase_type');
        });
    }
};
