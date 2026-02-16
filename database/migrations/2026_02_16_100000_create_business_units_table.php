<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('owner_name')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('tax_id', 30)->nullable();           // NPWP
            $table->string('business_type')->nullable();         // toko, jasa, dll
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        // Mapping COA per unit usaha — automasi akun transaksi
        Schema::create('business_unit_coa_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('account_key', 50);                   // e.g. kas_utama, piutang_usaha, hutang_usaha, modal_pemilik, pendapatan_utama, beban_gaji, dll
            $table->string('label');                             // human label: "Kas Utama"
            $table->foreignId('coa_id')->constrained('c_o_a_s')->restrictOnDelete();
            $table->timestamps();

            $table->unique(['business_unit_id', 'account_key']);
        });

        // Add business_unit_id to users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_unit_id')->nullable()->after('password')
                  ->constrained('business_units')->nullOnDelete();
            $table->boolean('is_active')->default(true)->after('business_unit_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['business_unit_id']);
            $table->dropColumn(['business_unit_id', 'is_active']);
        });

        Schema::dropIfExists('business_unit_coa_mappings');
        Schema::dropIfExists('business_units');
    }
};
