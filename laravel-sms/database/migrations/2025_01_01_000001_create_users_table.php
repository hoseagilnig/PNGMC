<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('username', 50)->unique();
            $table->string('password_hash');
            $table->string('full_name', 100);
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->enum('role', ['admin', 'finance', 'studentservices', 'hod'])->default('admin');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamp('last_login')->nullable();
            $table->timestamps();
            
            $table->index('username');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

