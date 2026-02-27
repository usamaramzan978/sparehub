<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table): void {
            $table->decimal('per_day_salary', 18, 2)->default(0)->after('salary_month');
            $table->unsignedTinyInteger('working_days')->default(0)->after('per_day_salary');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table): void {
            $table->dropColumn(['per_day_salary', 'working_days']);
        });
    }
};
