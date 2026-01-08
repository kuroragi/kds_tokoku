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
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique(); // 202401, 202402, etc
            $table->string('name'); // Januari 2024, Februari 2024, etc
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('year');
            $table->integer('month');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->text('description')->nullable();
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
        Schema::dropIfExists('periods');
    }
};
