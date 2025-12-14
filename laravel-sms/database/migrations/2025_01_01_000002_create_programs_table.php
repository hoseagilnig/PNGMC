<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id('program_id');
            $table->string('program_code', 20)->unique();
            $table->string('program_name', 200);
            $table->text('description')->nullable();
            $table->integer('duration_years')->default(1);
            $table->decimal('tuition_fee', 10, 2)->default(0.00);
            $table->enum('status', ['active', 'inactive', 'archived'])->default('active');
            $table->timestamps();
            
            $table->index('program_code');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
    }
};

