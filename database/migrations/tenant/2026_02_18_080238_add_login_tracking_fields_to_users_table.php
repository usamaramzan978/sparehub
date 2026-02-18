<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'last_login_ip')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            });
        }

        if (! Schema::hasColumn('users', 'two_factor_type')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->enum('two_factor_type', ['email', 'sms', 'app'])->nullable()->after('last_login_ip');
            });
        }

        if (! Schema::hasColumn('users', 'two_factor_secret')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->text('two_factor_secret')->nullable()->after('two_factor_type');
            });
        }

        if (! Schema::hasColumn('users', 'two_factor_verified_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('two_factor_verified_at')->nullable()->after('two_factor_secret');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $dropColumns = array_values(array_filter([
                Schema::hasColumn('users', 'last_login_ip') ? 'last_login_ip' : null,
                Schema::hasColumn('users', 'two_factor_type') ? 'two_factor_type' : null,
                Schema::hasColumn('users', 'two_factor_secret') ? 'two_factor_secret' : null,
                Schema::hasColumn('users', 'two_factor_verified_at') ? 'two_factor_verified_at' : null,
            ]));

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
