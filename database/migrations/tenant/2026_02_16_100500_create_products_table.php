<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignUuid('brand_id')->nullable()->constrained('brands')->nullOnDelete();
            $table->foreignUuid('default_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->foreignUuid('default_unit_id')->nullable()->constrained('units')->nullOnDelete();

            $table->string('sku', 60)->unique();
            $table->string('part_number', 60)->nullable();
            $table->string('barcode', 80)->nullable();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->boolean('track_stock')->default(true);
            $table->boolean('is_service_item')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('part_number');
            $table->unique('barcode');
            $table->index(['category_id', 'status']);
            $table->index(['brand_id', 'status']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
