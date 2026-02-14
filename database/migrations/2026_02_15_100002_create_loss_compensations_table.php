<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loss_compensations', function (Blueprint $table) {
            $table->id();
            $table->integer('source_year'); // the year the loss occurred
            $table->bigInteger('original_amount'); // original fiscal loss amount (positive number)
            $table->bigInteger('used_amount')->default(0); // total amount already compensated
            $table->bigInteger('remaining_amount'); // original - used
            $table->integer('expires_year'); // source_year + 5
            $table->timestamps();
            $table->softDeletes();
            $table->blameable();

            $table->index('source_year');
            $table->index('expires_year');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loss_compensations');
    }
};
