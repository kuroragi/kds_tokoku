<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_corrections', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->string('description');
            $table->enum('correction_type', ['positive', 'negative']); // koreksi positif / negatif
            $table->enum('category', ['beda_tetap', 'beda_waktu']); // permanent / timing difference
            $table->bigInteger('amount')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->index('year');
            $table->index(['year', 'correction_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_corrections');
    }
};
