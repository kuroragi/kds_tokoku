<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 64)->unique(); // Internal unique ID (e.g. TRX-20260227-00001)
            $table->string('gateway', 30);                  // manual, midtrans, xendit, etc.
            $table->string('gateway_reference')->nullable(); // External reference from payment provider

            // Polymorphic payable (usually Invoice, but extensible)
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('IDR');

            $table->enum('status', [
                'pending',      // Waiting for payment
                'processing',   // Being processed by gateway
                'success',      // Payment confirmed
                'failed',       // Payment failed
                'expired',      // Payment window expired
                'cancelled',    // Cancelled by user/admin
                'refunded',     // Refunded
            ])->default('pending');

            $table->string('payment_method')->nullable();   // transfer, va_bca, qris, etc.
            $table->string('payment_channel')->nullable();   // More specific channel info

            // Gateway-specific data (snap token, redirect URL, VA number, etc.)
            $table->json('gateway_data')->nullable();

            // Callback/webhook data from gateway
            $table->json('callback_data')->nullable();

            $table->text('notes')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['payable_type', 'payable_id'], 'payment_transactions_payable_index');
            $table->index(['user_id', 'status']);
            $table->index('gateway');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
