<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->foreignUuid('mechanic_id')->nullable()->after('job_card_service_id')->constrained('users')->nullOnDelete();
            $table->decimal('mechanic_charge', 18, 2)->default(0)->after('tax_amount');

            $table->index(['branch_id', 'mechanic_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table): void {
            $table->dropIndex(['branch_id', 'mechanic_id', 'created_at']);
            $table->dropConstrainedForeignId('mechanic_id');
            $table->dropColumn('mechanic_charge');
        });
    }
};
