<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxes', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->decimal('rate', 7, 4)->default(0);
            $table->boolean('is_inclusive')->default(false);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['status', 'rate']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxes');
    }
};
