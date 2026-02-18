<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Penyedia Saldo (Saldo Providers)
        Schema::create('saldo_providers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('type', ['e-wallet', 'bank', 'other'])->default('e-wallet');
            $table->text('description')->nullable();
            $table->decimal('initial_balance', 15, 2)->default(0);
            $table->decimal('current_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
            $table->unique(['business_unit_id', 'code']);
        });

        // Produk Saldo (Saldo Products)
        Schema::create('saldo_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->foreignId('saldo_provider_id')->nullable()->constrained('saldo_providers')->nullOnDelete();
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
            $table->unique(['business_unit_id', 'code']);
        });

        // Top Up Saldo
        Schema::create('saldo_topups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('saldo_provider_id')->constrained('saldo_providers')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 15, 2)->default(0);
            $table->date('topup_date');
            $table->string('method', 50)->default('transfer');
            $table->string('reference_no', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });

        // Transaksi Saldo (Sales)
        Schema::create('saldo_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained('business_units')->cascadeOnDelete();
            $table->foreignId('saldo_provider_id')->constrained('saldo_providers')->restrictOnDelete();
            $table->foreignId('saldo_product_id')->nullable()->constrained('saldo_products')->nullOnDelete();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->decimal('buy_price', 15, 2)->default(0);
            $table->decimal('sell_price', 15, 2)->default(0);
            $table->decimal('profit', 15, 2)->default(0);
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saldo_transactions');
        Schema::dropIfExists('saldo_topups');
        Schema::dropIfExists('saldo_products');
        Schema::dropIfExists('saldo_providers');
    }
};
