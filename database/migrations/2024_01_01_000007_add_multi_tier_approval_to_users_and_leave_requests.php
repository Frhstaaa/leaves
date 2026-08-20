<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add approver_1_id and approver_2_id to users table
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'approver_1_id')) {
                $table->foreignId('approver_1_id')->nullable()->after('manager_id')->constrained('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('users', 'approver_2_id')) {
                $table->foreignId('approver_2_id')->nullable()->after('approver_1_id')->constrained('users')->onDelete('set null');
            }
        });

        // 2. Add multi-tier approval tracking fields to leave_requests table
        Schema::table('leave_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('leave_requests', 'current_stage')) {
                $table->string('current_stage')->default('hrd')->after('status'); // approval_1, approval_2, hrd, completed
            }
            if (!Schema::hasColumn('leave_requests', 'approved_by_1')) {
                $table->foreignId('approved_by_1')->nullable()->after('current_stage')->constrained('users')->onDelete('set null');
                $table->text('approval_1_note')->nullable()->after('approved_by_1');
                $table->timestamp('approved_1_at')->nullable()->after('approval_1_note');
            }
            if (!Schema::hasColumn('leave_requests', 'approved_by_2')) {
                $table->foreignId('approved_by_2')->nullable()->after('approved_1_at')->constrained('users')->onDelete('set null');
                $table->text('approval_2_note')->nullable()->after('approved_by_2');
                $table->timestamp('approved_2_at')->nullable()->after('approval_2_note');
            }
            if (!Schema::hasColumn('leave_requests', 'approved_by_hrd')) {
                $table->foreignId('approved_by_hrd')->nullable()->after('approved_2_at')->constrained('users')->onDelete('set null');
                $table->text('approval_hrd_note')->nullable()->after('approved_by_hrd');
                $table->timestamp('approved_hrd_at')->nullable()->after('approval_hrd_note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by_1']);
            $table->dropForeign(['approved_by_2']);
            $table->dropForeign(['approved_by_hrd']);
            $table->dropColumn([
                'current_stage',
                'approved_by_1',
                'approval_1_note',
                'approved_1_at',
                'approved_by_2',
                'approval_2_note',
                'approved_2_at',
                'approved_by_hrd',
                'approval_hrd_note',
                'approved_hrd_at',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approver_1_id']);
            $table->dropForeign(['approver_2_id']);
            $table->dropColumn(['approver_1_id', 'approver_2_id']);
        });
    }
};
