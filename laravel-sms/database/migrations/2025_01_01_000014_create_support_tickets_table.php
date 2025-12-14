<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id('ticket_id');
            $table->string('ticket_number', 50)->unique();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 200);
            $table->text('description');
            $table->enum('category', ['academic', 'financial', 'dormitory', 'welfare', 'technical', 'other'])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed', 'cancelled'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index('ticket_number');
            $table->index('student_id');
            $table->index('status');
            $table->index('priority');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};

