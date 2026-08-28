<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Try Schema change, or fallback to raw SQL for MySQL/MariaDB
        try {
            Schema::table('leave_quotas', function (Blueprint $table) {
                $table->decimal('total_quota', 8, 2)->default(12.00)->change();
                $table->decimal('used_quota', 8, 2)->default(0.00)->change();
                $table->decimal('remaining_quota', 8, 2)->default(12.00)->change();
            });
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `leave_quotas` MODIFY `total_quota` DECIMAL(8,2) NOT NULL DEFAULT 12.00");
                DB::statement("ALTER TABLE `leave_quotas` MODIFY `used_quota` DECIMAL(8,2) NOT NULL DEFAULT 0.00");
                DB::statement("ALTER TABLE `leave_quotas` MODIFY `remaining_quota` DECIMAL(8,2) NOT NULL DEFAULT 12.00");
            } catch (\Throwable $e2) {
                // Ignore if already decimal or not supported
            }
        }
    }

    public function down(): void
    {
        try {
            Schema::table('leave_quotas', function (Blueprint $table) {
                $table->integer('total_quota')->default(12)->change();
                $table->integer('used_quota')->default(0)->change();
                $table->integer('remaining_quota')->default(12)->change();
            });
        } catch (\Throwable $e) {
            try {
                DB::statement("ALTER TABLE `leave_quotas` MODIFY `total_quota` INT NOT NULL DEFAULT 12");
                DB::statement("ALTER TABLE `leave_quotas` MODIFY `used_quota` INT NOT NULL DEFAULT 0");
                DB::statement("ALTER TABLE `leave_quotas` MODIFY `remaining_quota` INT NOT NULL DEFAULT 12");
            } catch (\Throwable $e2) {}
        }
    }
};
