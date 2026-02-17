<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_card_services', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('job_card_id')->constrained('job_cards')->cascadeOnDelete();
            $table->foreignUuid('service_catalog_id')->nullable()->constrained('service_catalog')->nullOnDelete();
            $table->foreignUuid('technician_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('service_name', 160);
            $table->decimal('qty', 18, 3)->default(1);
            $table->decimal('rate', 18, 2)->default(0);
            $table->decimal('line_total', 18, 2)->default(0);
            $table->string('status', 30)->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->index(['job_card_id', 'status']);
            $table->index(['technician_id', 'status']);
            $table->index('service_catalog_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_card_services');
    }
};
