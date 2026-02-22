<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Invoice Table ──
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot plan details at time of invoice
            $table->string('plan_name');
            $table->decimal('plan_price', 12, 2);
            $table->integer('duration_days');

            // Totals
            $table->decimal('subtotal', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('total', 12, 2);

            // Status
            $table->enum('status', ['unpaid', 'paid', 'cancelled', 'expired'])->default('unpaid');
            $table->date('issued_at');
            $table->date('due_at');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        // ── Skip Email Verification column ──
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('skip_email_verification')->default(false)->after('email_otp_expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('skip_email_verification');
        });
    }
};
