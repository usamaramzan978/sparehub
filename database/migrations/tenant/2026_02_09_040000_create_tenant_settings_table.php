<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenant_settings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('branch_id')->constrained('branches')->cascadeOnDelete();

            $table->string('company_name')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('support_email')->nullable();
            $table->string('support_phone', 50)->nullable();

            $table->boolean('enable_two_factor')->default(false);

            $table->boolean('notify_email')->default(true);

            $table->boolean('enable_otp')->default(false);
            $table->unsignedTinyInteger('otp_length')->default(6);
            $table->unsignedSmallInteger('otp_expiry_minutes')->default(10);

            $table->timestamps();

            $table->unique('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_settings');
    }
};
