<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_returns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignUuid('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('return_no', 40);
            $table->date('return_date');
            $table->string('status', 30)->default('draft');
            $table->decimal('grand_total', 18, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['branch_id', 'return_no']);
            $table->index(['branch_id', 'return_date', 'status']);
            $table->index(['vendor_id', 'return_date']);
            $table->index(['purchase_id', 'created_at']);
        });

        Schema::create('purchase_return_items', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
            $table->foreignUuid('purchase_item_id')->nullable()->constrained('purchase_items')->nullOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 18, 3);
            $table->decimal('unit_cost', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index(['purchase_return_id', 'created_at']);
            $table->index('purchase_item_id');
            $table->index(['product_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
