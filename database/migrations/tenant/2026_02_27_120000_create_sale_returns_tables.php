<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_returns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignUuid('sale_id')->nullable()->constrained('sales')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_no', 40);
            $table->date('return_date');
            $table->string('status', 30)->default('draft');
            $table->decimal('sub_total', 18, 2)->default(0);
            $table->decimal('tax_total', 18, 2)->default(0);
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'return_no']);
            $table->index(['branch_id', 'return_date', 'status']);
            $table->index(['customer_id', 'return_date']);
            $table->index(['sale_id', 'created_at']);
        });

        Schema::create('sale_return_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('sale_return_id')->constrained('sale_returns')->cascadeOnDelete();
            $table->foreignUuid('sale_item_id')->nullable()->constrained('sale_items')->nullOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignUuid('tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->decimal('qty', 18, 3);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['sale_return_id', 'created_at']);
            $table->index('sale_item_id');
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_return_items');
        Schema::dropIfExists('sale_returns');
    }
};
