<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('correspondence', function (Blueprint $table) {
            $table->id('correspondence_id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->enum('correspondence_type', ['email', 'letter', 'phone', 'invoice', 'rejection_letter', 'acceptance_letter', 'requirements_letter']);
            $table->string('subject', 200);
            $table->text('message');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('sent_date');
            $table->timestamp('sent_at')->useCurrent();
            $table->string('attachment_path', 500)->nullable();
            $table->enum('status', ['draft', 'sent', 'delivered', 'failed'])->default('draft');
            
            $table->index('application_id');
            $table->index('sent_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('correspondence');
    }
};

