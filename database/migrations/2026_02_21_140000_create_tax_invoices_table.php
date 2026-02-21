<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── Faktur Pajak / Tax Invoices ───
        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained();
            $table->string('invoice_type', 20); // keluaran (output/sales), masukan (input/purchase)
            $table->string('faktur_number', 50)->nullable(); // nomor seri faktur pajak
            $table->date('invoice_date');
            $table->string('tax_period', 7); // YYYY-MM
            $table->string('partner_name'); // nama lawan transaksi
            $table->string('partner_npwp', 30)->nullable();
            $table->decimal('dpp', 18, 2)->default(0); // Dasar Pengenaan Pajak
            $table->decimal('ppn', 18, 2)->default(0); // PPN
            $table->decimal('ppnbm', 18, 2)->default(0); // PPnBM (optional)
            $table->string('status', 20)->default('draft'); // draft, approved, reported, cancelled
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['business_unit_id', 'tax_period']);
            $table->index(['invoice_type', 'tax_period']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
    }
};
