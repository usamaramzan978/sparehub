<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_parts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('qty', 18, 3)->default(1);
            $table->decimal('cost', 18, 2)->default(0);
            $table->decimal('mrp', 18, 2)->default(0);
            $table->decimal('retail_price', 18, 2)->default(0);
            $table->decimal('wholesale_price', 18, 2)->default(0);
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->timestamps();

            $table->index('job_card_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_parts');
    }
};
