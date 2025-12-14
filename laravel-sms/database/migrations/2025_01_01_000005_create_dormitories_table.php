<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitories', function (Blueprint $table) {
            $table->id('dormitory_id');
            $table->string('dormitory_name', 100);
            $table->string('building_name', 100)->nullable();
            $table->string('room_number', 20)->nullable();
            $table->integer('capacity')->default(1);
            $table->integer('current_occupancy')->default(0);
            $table->enum('gender_type', ['Male', 'Female', 'Mixed'])->default('Mixed');
            $table->decimal('monthly_fee', 10, 2)->default(0.00);
            $table->enum('status', ['available', 'occupied', 'maintenance', 'closed'])->default('available');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index('dormitory_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitories');
    }
};

