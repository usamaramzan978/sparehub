<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_cards', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('vehicle_id')->nullable()->constrained('customer_vehicles')->nullOnDelete();
            $table->foreignUuid('assigned_employee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('job_no', 40);
            $table->date('job_date');
            $table->string('status', 30)->default('new');
            $table->decimal('meter_reading', 18, 3)->nullable();
            $table->decimal('next_reading', 18, 3)->nullable();
            $table->unsignedInteger('total_visits')->default(0);
            $table->text('remarks')->nullable();
            $table->timestamp('in_time')->nullable();
            $table->timestamp('out_time')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'job_no']);
            $table->index(['branch_id', 'status', 'job_date']);
            $table->index(['customer_id', 'job_date']);
            $table->index(['vehicle_id', 'job_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_cards');
    }
};
