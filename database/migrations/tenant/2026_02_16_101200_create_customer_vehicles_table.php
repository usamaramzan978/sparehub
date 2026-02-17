<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_vehicles', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('registration_no', 50)->unique();
            $table->string('model', 120)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('chassis_no', 80)->nullable();
            $table->string('engine_no', 80)->nullable();
            $table->decimal('meter_reading', 18, 3)->default(0);
            $table->timestamps();

            $table->index('model');
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_vehicles');
    }
};
