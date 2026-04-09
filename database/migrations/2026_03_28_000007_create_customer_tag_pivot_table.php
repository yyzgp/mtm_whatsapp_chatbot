<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_tag_pivot', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('customer_tags')->cascadeOnDelete();
            $table->primary(['customer_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_tag_pivot');
    }
};
