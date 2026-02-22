<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System settings table for superadmin configurations
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Add OTP columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('email_otp', 6)->nullable()->after('email_verified_at');
            $table->timestamp('email_otp_expires_at')->nullable()->after('email_otp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['email_otp', 'email_otp_expires_at']);
        });
    }
};
