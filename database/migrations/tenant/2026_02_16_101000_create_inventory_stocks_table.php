<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->decimal('qty_on_hand', 18, 3)->default(0);
            $table->decimal('qty_reserved', 18, 3)->default(0);
            $table->decimal('avg_cost', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['product_id', 'branch_id']);
            $table->index(['branch_id']);
            $table->index(['product_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
