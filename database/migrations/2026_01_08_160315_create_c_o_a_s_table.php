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
        Schema::create('c_o_a_s', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('type', ['aktiva', 'pasiva', 'modal', 'pendapatan', 'beban']);
            $table->unsignedBigInteger('parent_code')->nullable();
            $table->integer('level')->default(1);
            $table->integer('order')->default(1);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_leaf_account')->default(false);
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
        Schema::dropIfExists('c_o_a_s');
    }
};
