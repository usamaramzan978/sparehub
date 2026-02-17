<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('job_card_id')->nullable()->constrained('job_cards')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('invoice_no', 40);
            $table->date('invoice_date');
            $table->string('status', 30)->default('draft');
            $table->string('invoice_type', 20)->default('product');
            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('discount_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->decimal('paid_total', 18, 2)->default(0);
            $table->decimal('balance_due', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'invoice_no']);
            $table->index(['branch_id', 'invoice_date', 'status']);
            $table->index(['customer_id', 'invoice_date']);
            $table->index(['status', 'invoice_type']);
        });

        Schema::create('sale_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignUuid('service_catalog_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->foreignUuid('job_card_service_id')->nullable()->constrained('job_card_services')->nullOnDelete();
            $table->string('line_type', 20);
            $table->string('description', 200)->nullable();
            $table->decimal('qty', 18, 3)->default(1);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('discount_amount', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index('sale_id');
            $table->index('product_id');
            $table->index('service_catalog_id');
            $table->index('job_card_service_id');
            $table->index(['line_type', 'created_at']);
            $table->index(['branch_id', 'product_id', 'created_at']);
        });

        Schema::create('sale_payments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_id')->constrained('sales')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('payment_method', 30)->default('cash');
            $table->decimal('amount', 18, 2);
            $table->string('reference_no', 60)->nullable();
            $table->timestamp('paid_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['sale_id', 'paid_at']);
            $table->index(['payment_method', 'paid_at']);
            $table->index(['branch_id', 'paid_at']);
        });

        Schema::create('sale_holds', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hold_no', 40);
            $table->json('payload');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'hold_no']);
            $table->index(['branch_id', 'created_at']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_holds');
        Schema::dropIfExists('sale_payments');
        Schema::dropIfExists('sale_items');
        Schema::dropIfExists('sales');
    }
};
