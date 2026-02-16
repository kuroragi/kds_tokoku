<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Kategori Stok — tipe produk: barang, jasa, saldo
        Schema::create('stock_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('type', ['barang', 'jasa', 'saldo']);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });

        // 2) Grup Kategori — pengelompokan detail (aksesoris, kartu, dll)
        //    dengan mapping akun persediaan, pendapatan, dan biaya
        Schema::create('category_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('stock_category_id')->constrained('stock_categories')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->text('description')->nullable();

            // COA mappings
            $table->foreignId('coa_inventory_id')->nullable()->constrained('c_o_a_s')->nullOnDelete();   // Akun Persediaan
            $table->foreignId('coa_revenue_id')->nullable()->constrained('c_o_a_s')->nullOnDelete();     // Akun Pendapatan
            $table->foreignId('coa_expense_id')->nullable()->constrained('c_o_a_s')->nullOnDelete();     // Akun HPP / Biaya

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });

        // 3) Satuan — unit of measure
        //    Ada satuan default sistem (business_unit_id = null, is_system_default = true)
        //    Saat unit usaha dibuat/dipilih, default di-duplicate ke unit usaha tsb
        Schema::create('unit_of_measures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->nullable()->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('symbol', 10)->nullable();           // e.g., "pcs", "kg", "ltr"
            $table->text('description')->nullable();
            $table->boolean('is_system_default')->default(false); // true = template bawaan sistem
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            // business_unit_id + code unique (NULL business_unit means system level)
            $table->unique(['business_unit_id', 'code']);
        });

        // 4) Stok — produk/jasa yang dijual/dibeli
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('category_group_id')->constrained('category_groups')->restrictOnDelete();
            $table->foreignId('unit_of_measure_id')->constrained('unit_of_measures')->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('barcode', 50)->nullable();
            $table->text('description')->nullable();
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->decimal('min_stock', 15, 2)->default(0);
            $table->decimal('current_stock', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('unit_of_measures');
        Schema::dropIfExists('category_groups');
        Schema::dropIfExists('stock_categories');
    }
};
