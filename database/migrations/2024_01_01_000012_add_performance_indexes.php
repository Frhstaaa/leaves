<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations for query performance optimization.
     */
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            // Indexing for rapid multi-tier queries & dashboard filtering
            if (!Schema::hasIndex('leave_requests', 'idx_lr_user_status')) {
                $table->index(['user_id', 'status'], 'idx_lr_user_status');
            }
            if (!Schema::hasIndex('leave_requests', 'idx_lr_stage_status')) {
                $table->index(['current_stage', 'status'], 'idx_lr_stage_status');
            }
            if (!Schema::hasIndex('leave_requests', 'idx_lr_dates')) {
                $table->index(['start_date', 'end_date'], 'idx_lr_dates');
            }
        });

        Schema::table('leave_quotas', function (Blueprint $table) {
            if (!Schema::hasIndex('leave_quotas', 'idx_lq_user_year')) {
                $table->index(['user_id', 'year'], 'idx_lq_user_year');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropIndex('idx_lr_user_status');
            $table->dropIndex('idx_lr_stage_status');
            $table->dropIndex('idx_lr_dates');
        });

        Schema::table('leave_quotas', function (Blueprint $table) {
            $table->dropIndex('idx_lq_user_year');
        });
    }
};
