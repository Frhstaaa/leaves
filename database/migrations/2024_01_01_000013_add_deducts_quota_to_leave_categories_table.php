<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('leave_categories', 'deducts_quota')) {
            Schema::table('leave_categories', function (Blueprint $table) {
                $table->boolean('deducts_quota')->default(false)->after('requires_attachment');
            });
        }

        // Set deducts_quota = true for Cuti Tahunan & Cuti Haid
        DB::table('leave_categories')
            ->whereIn(DB::raw('LOWER(name)'), ['cuti tahunan', 'cuti haid'])
            ->update(['deducts_quota' => true]);

        // Set deducts_quota = false for other categories
        DB::table('leave_categories')
            ->whereNotIn(DB::raw('LOWER(name)'), ['cuti tahunan', 'cuti haid'])
            ->update(['deducts_quota' => false]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('leave_categories', 'deducts_quota')) {
            Schema::table('leave_categories', function (Blueprint $table) {
                $table->dropColumn('deducts_quota');
            });
        }
    }
};
