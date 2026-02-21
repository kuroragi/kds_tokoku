<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');            // Trial, Basic, Medium, Premium
            $table->string('slug')->unique();  // trial, basic, medium, premium
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('duration_days')->default(30); // 30 = bulanan, 14 = trial
            $table->integer('max_users')->default(1);      // 0 = unlimited
            $table->integer('max_business_units')->default(1); // 0 = unlimited
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');   // e.g. 'purchase', 'payroll', 'bank_reconciliation'
            $table->string('feature_label'); // e.g. 'Pembelian (Purchase)'
            $table->boolean('is_enabled')->default(false);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('plans');
    }
};
