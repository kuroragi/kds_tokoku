<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('c_o_a_s', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('id')
                ->constrained('business_units')->cascadeOnDelete();

            $table->unsignedBigInteger('template_id')->nullable()->after('business_unit_id');
            $table->boolean('is_locked')->default(false)->after('is_leaf_account');

            $table->index(['business_unit_id', 'code']);
            $table->index('template_id');

            $table->foreign('template_id')
                ->references('id')->on('coa_templates')
                ->nullOnDelete();
        });

        // Drop old unique on code (global), replace with unique per business_unit
        Schema::table('c_o_a_s', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->unique(['business_unit_id', 'code'], 'c_o_a_s_business_unit_code_unique');
        });
    }

    public function down(): void
    {
        Schema::table('c_o_a_s', function (Blueprint $table) {
            $table->dropUnique('c_o_a_s_business_unit_code_unique');
            $table->unique('code');

            $table->dropForeign(['template_id']);
            $table->dropForeign(['business_unit_id']);
            $table->dropIndex(['business_unit_id', 'code']);
            $table->dropIndex(['template_id']);

            $table->dropColumn(['business_unit_id', 'template_id', 'is_locked']);
        });
    }
};
