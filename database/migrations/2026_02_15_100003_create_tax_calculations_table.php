<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_calculations', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->bigInteger('commercial_profit')->default(0);
            $table->bigInteger('total_positive_correction')->default(0);
            $table->bigInteger('total_negative_correction')->default(0);
            $table->bigInteger('fiscal_profit')->default(0);
            $table->bigInteger('loss_compensation_amount')->default(0);
            $table->bigInteger('taxable_income')->default(0);
            $table->decimal('tax_rate', 5, 2)->default(22.00);
            $table->bigInteger('tax_amount')->default(0);
            $table->enum('status', ['draft', 'finalized'])->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('id_journal_master')->nullable(); // linked tax journal
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->index('year');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_calculations');
    }
};
