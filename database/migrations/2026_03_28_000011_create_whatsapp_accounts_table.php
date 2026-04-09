<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone_number_id', 100);
            $table->string('waba_id', 100);
            $table->text('access_token');
            $table->string('verify_token');
            $table->boolean('is_active')->default(true);
            $table->boolean('ai_enabled')->default(true);
            $table->text('ai_prompt')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_accounts');
    }
};
