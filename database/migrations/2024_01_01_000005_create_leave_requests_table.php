<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->string('submission_type')->default('PERMOHONAN'); // PEMBERITAHUAN, PERMOHONAN
            $table->boolean('approval_agreed')->default(true);
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_category_id')->constrained('leave_categories')->onDelete('cascade');
            $table->string('unit')->default('hari'); // hari, jam
            $table->decimal('amount', 8, 2);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->string('attachment_name')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('approval_note')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
