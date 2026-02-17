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
        Schema::create('login_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id');
            $table->uuid('type_id');
            $table->string('type', 20)->index();
            $table->string('email')->index();
            $table->string('password');
            $table->boolean('status')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'type', 'email'], 'login_maps_tenant_type_email_unique');
            $table->unique(['tenant_id', 'type', 'type_id'], 'login_maps_tenant_type_type_id_unique');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnUpdate()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_maps');
    }
};
