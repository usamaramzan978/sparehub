<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_catalog', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('default_tax_id')->nullable()->constrained('taxes')->nullOnDelete();
            $table->string('code', 30);
            $table->string('name', 160);
            $table->string('category', 80)->nullable();
            $table->decimal('base_price', 18, 2)->default(0);
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'status']);
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalog');
    }
};
