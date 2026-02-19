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
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('payment_source', 50)->nullable()->after('payment_type')
                ->comment('COA key: kas_utama, kas_kecil, bank_utama');
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->string('payment_source', 50)->nullable()->after('payment_method')
                ->comment('COA key: kas_utama, kas_kecil, bank_utama');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('payment_source');
        });

        Schema::table('purchase_payments', function (Blueprint $table) {
            $table->dropColumn('payment_source');
        });
    }
};
