<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_commission_rules', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('service_catalog_id')->constrained('service_catalog')->cascadeOnDelete();
            $table->decimal('total_amount', 18, 2)->default(0);
            $table->string('commission_type', 20)->default('fixed');
            $table->decimal('commission_value', 18, 2)->default(0);
            $table->decimal('payable_amount', 18, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'sort_order']);
            $table->index(['user_id', 'service_catalog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_commission_rules');
    }
};
