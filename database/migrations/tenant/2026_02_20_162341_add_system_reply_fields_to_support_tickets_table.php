<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->text('system_reply')->nullable()->after('status');
            $table->timestamp('replied_at')->nullable()->after('system_reply');
            $table->string('replied_by_system_user_id', 64)->nullable()->after('replied_at');
            $table->string('replied_by_system_user_name', 120)->nullable()->after('replied_by_system_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            $table->dropColumn([
                'system_reply',
                'replied_at',
                'replied_by_system_user_id',
                'replied_by_system_user_name',
            ]);
        });
    }
};
