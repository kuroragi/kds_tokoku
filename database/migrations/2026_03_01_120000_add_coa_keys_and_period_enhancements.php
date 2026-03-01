<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. StockCategory: tambah COA key mapping (seperti AssetCategory)
        Schema::table('stock_categories', function (Blueprint $table) {
            $table->string('coa_inventory_key', 50)->nullable()->after('type')
                ->comment('Account key for inventory/persediaan (aktiva)');
            $table->string('coa_hpp_key', 50)->nullable()->after('coa_inventory_key')
                ->comment('Account key for HPP/COGS (beban)');
            $table->string('coa_revenue_key', 50)->nullable()->after('coa_hpp_key')
                ->comment('Account key for revenue/pendapatan');
        });

        // 2. CategoryGroup: tambah COA key mapping (selain direct COA ID yang sudah ada)
        Schema::table('category_groups', function (Blueprint $table) {
            $table->string('coa_inventory_key', 50)->nullable()->after('coa_expense_id')
                ->comment('Account key override for inventory');
            $table->string('coa_revenue_key', 50)->nullable()->after('coa_inventory_key')
                ->comment('Account key override for revenue');
            $table->string('coa_expense_key', 50)->nullable()->after('coa_revenue_key')
                ->comment('Account key override for HPP/expense');
        });

        // 3. Period: tambah business_unit_id agar per unit usaha
        if (!Schema::hasColumn('periods', 'business_unit_id')) {
            Schema::table('periods', function (Blueprint $table) {
                $table->foreignId('business_unit_id')->nullable()->after('id')
                    ->constrained('business_units')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('stock_categories', function (Blueprint $table) {
            $table->dropColumn(['coa_inventory_key', 'coa_hpp_key', 'coa_revenue_key']);
        });

        Schema::table('category_groups', function (Blueprint $table) {
            $table->dropColumn(['coa_inventory_key', 'coa_revenue_key', 'coa_expense_key']);
        });

        if (Schema::hasColumn('periods', 'business_unit_id')) {
            Schema::table('periods', function (Blueprint $table) {
                $table->dropForeign(['business_unit_id']);
                $table->dropColumn('business_unit_id');
            });
        }
    }
};
