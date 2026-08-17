<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit_type')->default('hari'); // hari, jam
            $table->boolean('requires_attachment')->default(false);
            $table->integer('default_quota')->default(12);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_categories');
    }
};
