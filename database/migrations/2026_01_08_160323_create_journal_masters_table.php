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
        Schema::create('journal_masters', function (Blueprint $table) {
            $table->id();
            $table->string('journal_no', 50)->unique();
            $table->date('journal_date');
            $table->string('reference', 100)->nullable();
            $table->text('description')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);
            $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_masters');
    }
};
