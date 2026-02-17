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
        Schema::table('system_users', function (Blueprint $table): void {
            $table->timestamp('last_login_at')->nullable()->after('status');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->enum('two_factor_type', ['email', 'sms', 'app'])->nullable()->after('last_login_ip');
            $table->text('two_factor_secret')->nullable()->after('two_factor_type');
            $table->timestamp('two_factor_verified_at')->nullable()->after('two_factor_secret');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('system_users', function (Blueprint $table): void {
            $table->dropColumn([
                'last_login_at',
                'last_login_ip',
                'two_factor_type',
                'two_factor_secret',
                'two_factor_verified_at',
            ]);
        });
    }
};
