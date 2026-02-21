<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── 1. Projects / Job Orders ───
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_unit_id')->constrained();
            $table->string('project_code', 30)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('budget', 18, 2)->default(0);
            $table->decimal('actual_cost', 18, 2)->default(0);
            $table->decimal('revenue', 18, 2)->default(0);
            $table->string('status', 20)->default('planning');
            // status: planning, active, on_hold, completed, cancelled
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['business_unit_id', 'status']);
            $table->index(['project_code']);
        });

        // ─── 2. Project Cost Items ───
        Schema::create('project_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('cost_date');
            $table->string('category', 50); // material, labor, overhead, other
            $table->string('description');
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('journal_master_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['project_id', 'cost_date']);
            $table->index(['category']);
        });

        // ─── 3. Project Revenue Items ───
        Schema::create('project_revenues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->date('revenue_date');
            $table->string('description');
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('journal_master_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sale_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index(['project_id', 'revenue_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_revenues');
        Schema::dropIfExists('project_costs');
        Schema::dropIfExists('projects');
    }
};
