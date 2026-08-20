<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'approver_1_id')) {
                $table->unsignedBigInteger('approver_1_id')->nullable()->after('manager_id');
                $table->foreign('approver_1_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'approver_2_id')) {
                $table->unsignedBigInteger('approver_2_id')->nullable()->after('approver_1_id');
                $table->foreign('approver_2_id')->references('id')->on('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('departments', 'approval_type')) {
                $table->string('approval_type')->default('3_tier')->after('approver_2_id'); // 3_tier, 2_tier, 1_tier, custom
            }
            if (!Schema::hasColumn('departments', 'description')) {
                $table->text('description')->nullable()->after('approval_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (Schema::hasColumn('departments', 'approver_1_id')) {
                $table->dropForeign(['approver_1_id']);
                $table->dropColumn('approver_1_id');
            }
            if (Schema::hasColumn('departments', 'approver_2_id')) {
                $table->dropForeign(['approver_2_id']);
                $table->dropColumn('approver_2_id');
            }
            if (Schema::hasColumn('departments', 'approval_type')) {
                $table->dropColumn('approval_type');
            }
            if (Schema::hasColumn('departments', 'description')) {
                $table->dropColumn('description');
            }
        });
    }
};
