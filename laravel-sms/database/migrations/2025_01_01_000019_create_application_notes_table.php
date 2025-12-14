<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_notes', function (Blueprint $table) {
            $table->id('note_id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('note_text');
            $table->boolean('is_internal')->default(true);
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_notes');
    }
};

