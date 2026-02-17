<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Jabatan — Posisi karyawan (sama seperti UnitOfMeasure: system default + per-unit)
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_system_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });

        // 2) Karyawan — data karyawan per unit usaha
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('position_id')->nullable()->constrained('positions')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('nik', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->date('join_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });

        // 3) Pelanggan — data pelanggan per unit usaha
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });

        // 4) Vendor — data vendor global (tidak di-scope per unit, tapi di-attach via pivot)
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30);
            $table->string('name');
            $table->enum('type', ['distributor', 'supplier_bahan', 'jasa', 'lainnya'])->default('lainnya');
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('npwp', 30)->nullable();
            $table->string('nik', 20)->nullable();
            $table->boolean('is_pph23')->default(false);
            $table->decimal('pph23_rate', 5, 2)->default(2.00);
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number', 30)->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('website')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique('code');
        });

        // 5) Pivot — relasi many-to-many vendor ↔ business unit
        Schema::create('business_unit_vendor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['business_unit_id', 'vendor_id']);
        });

        // 6) Partner — mitra bisnis per unit usaha
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('type')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('contact_person')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partners');
        Schema::dropIfExists('business_unit_vendor');
        Schema::dropIfExists('vendors');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
    }
};
