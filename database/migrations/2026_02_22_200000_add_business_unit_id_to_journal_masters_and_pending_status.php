<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add business_unit_id to journal_masters
        Schema::table('journal_masters', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('id')
                ->constrained('business_units')->nullOnDelete();
        });

        // Add pending status to subscriptions
        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('pending','active','expired','cancelled','grace') DEFAULT 'active'");
    }

    public function down(): void
    {
        Schema::table('journal_masters', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn('business_unit_id');
        });

        DB::statement("ALTER TABLE subscriptions MODIFY COLUMN status ENUM('active','expired','cancelled','grace') DEFAULT 'active'");
    }
};
