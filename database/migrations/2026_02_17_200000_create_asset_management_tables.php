<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Kategori Aset
        Schema::create('asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30);
            $table->string('name', 255);
            $table->string('description')->nullable();
            $table->integer('useful_life_months')->default(60); // default 5 tahun
            $table->string('depreciation_method', 30)->default('straight_line'); // straight_line, declining_balance
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
        });

        // 2. Aset (Master + Pengadaan)
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_category_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->string('code', 30);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->bigInteger('acquisition_cost')->default(0);
            $table->integer('useful_life_months')->default(60);
            $table->bigInteger('salvage_value')->default(0);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->string('location', 255)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('condition', 30)->default('good'); // good, fair, poor
            $table->string('status', 30)->default('active'); // active, disposed, under_repair
            $table->unsignedBigInteger('journal_master_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['business_unit_id', 'code']);
            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
            $table->foreign('journal_master_id')->references('id')->on('journal_masters')->nullOnDelete();
        });

        // 3. Penyusutan Aset
        Schema::create('asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('period_id')->nullable();
            $table->date('depreciation_date');
            $table->bigInteger('depreciation_amount')->default(0);
            $table->bigInteger('accumulated_depreciation')->default(0);
            $table->bigInteger('book_value')->default(0);
            $table->unsignedBigInteger('journal_master_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->unique(['asset_id', 'period_id']);
            $table->foreign('period_id')->references('id')->on('periods')->nullOnDelete();
            $table->foreign('journal_master_id')->references('id')->on('journal_masters')->nullOnDelete();
        });

        // 4. Mutasi Lokasi
        Schema::create('asset_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('transfer_date');
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();
            $table->unsignedBigInteger('from_business_unit_id')->nullable();
            $table->unsignedBigInteger('to_business_unit_id')->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->foreign('from_business_unit_id')->references('id')->on('business_units')->nullOnDelete();
            $table->foreign('to_business_unit_id')->references('id')->on('business_units')->nullOnDelete();
        });

        // 5. Disposal/Pelepasan Aset
        Schema::create('asset_disposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->date('disposal_date');
            $table->string('disposal_method', 30)->default('scrapped'); // sold, scrapped, donated
            $table->bigInteger('disposal_amount')->default(0);
            $table->bigInteger('book_value_at_disposal')->default(0);
            $table->bigInteger('gain_loss')->default(0);
            $table->unsignedBigInteger('journal_master_id')->nullable();
            $table->string('buyer_info', 255)->nullable();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->foreign('journal_master_id')->references('id')->on('journal_masters')->nullOnDelete();
        });

        // 6. Perbaikan Aset (Opsional)
        Schema::create('asset_repairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->date('repair_date');
            $table->text('description')->nullable();
            $table->bigInteger('cost')->default(0);
            $table->string('status', 30)->default('pending'); // pending, in_progress, completed
            $table->date('completed_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->foreign('vendor_id')->references('id')->on('vendors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_repairs');
        Schema::dropIfExists('asset_disposals');
        Schema::dropIfExists('asset_transfers');
        Schema::dropIfExists('asset_depreciations');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('asset_categories');
    }
};
