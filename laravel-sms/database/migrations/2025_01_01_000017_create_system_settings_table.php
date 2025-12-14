<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->string('setting_type', 50)->default('string');
            $table->text('description')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
        
        // Insert default settings
        DB::table('system_settings')->insert([
            ['setting_key' => 'college_name', 'setting_value' => 'PNG Maritime College', 'setting_type' => 'string', 'description' => 'Name of the institution'],
            ['setting_key' => 'academic_year', 'setting_value' => '2025', 'setting_type' => 'string', 'description' => 'Current academic year'],
            ['setting_key' => 'semester', 'setting_value' => '1', 'setting_type' => 'string', 'description' => 'Current semester'],
            ['setting_key' => 'currency', 'setting_value' => 'PGK', 'setting_type' => 'string', 'description' => 'Currency code'],
            ['setting_key' => 'invoice_prefix', 'setting_value' => 'INV-', 'setting_type' => 'string', 'description' => 'Prefix for invoice numbers'],
            ['setting_key' => 'payment_prefix', 'setting_value' => 'PAY-', 'setting_type' => 'string', 'description' => 'Prefix for payment numbers'],
            ['setting_key' => 'ticket_prefix', 'setting_value' => 'TKT-', 'setting_type' => 'string', 'description' => 'Prefix for ticket numbers'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

