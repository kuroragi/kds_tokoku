<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Asset Categories: tambah COA mapping keys ──
        Schema::table('asset_categories', function (Blueprint $table) {
            // Key mapping ke akun COA aset (misal: 1201.003 Kendaraan)
            $table->string('coa_asset_key', 50)->nullable()->after('depreciation_method');
            // Key mapping ke akun COA akumulasi penyusutan (misal: 1202.002 Akum. Peny. Kendaraan)
            $table->string('coa_accumulated_dep_key', 50)->nullable()->after('coa_asset_key');
            // Key mapping ke akun COA beban penyusutan (misal: 5304.002 Beban Peny. Kendaraan)
            $table->string('coa_expense_dep_key', 50)->nullable()->after('coa_accumulated_dep_key');
        });

        // ── 2. Assets: tambah acquisition_type dan kolom terkait ──
        Schema::table('assets', function (Blueprint $table) {
            $table->string('acquisition_type', 30)->default('purchase_cash')->after('status');
            // Sumber dana khusus opening_balance: equity, debt, mixed
            $table->string('funding_source', 20)->nullable()->after('acquisition_type');
            // Akumulasi penyusutan awal (untuk aset lama / saldo awal)
            $table->bigInteger('initial_accumulated_depreciation')->default(0)->after('funding_source');
            // Sisa hutang (untuk opening_balance dengan sumber hutang)
            $table->bigInteger('remaining_debt_amount')->default(0)->after('initial_accumulated_depreciation');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'acquisition_type',
                'funding_source',
                'initial_accumulated_depreciation',
                'remaining_debt_amount',
            ]);
        });

        Schema::table('asset_categories', function (Blueprint $table) {
            $table->dropColumn([
                'coa_asset_key',
                'coa_accumulated_dep_key',
                'coa_expense_dep_key',
            ]);
        });
    }
};
