<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create phone numbers table
        Schema::create('whatsapp_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('phone_number_id');
            $table->boolean('is_active')->default(true);
            $table->boolean('ai_enabled')->default(true);
            $table->text('ai_prompt')->nullable();
            $table->timestamps();
        });

        // 2. Migrate existing data: create a phone_number row for each existing account
        $accounts = DB::table('whatsapp_accounts')->get();
        foreach ($accounts as $account) {
            DB::table('whatsapp_phone_numbers')->insert([
                'whatsapp_account_id' => $account->id,
                'name' => $account->name,
                'phone_number_id' => $account->phone_number_id,
                'is_active' => $account->is_active,
                'ai_enabled' => $account->ai_enabled,
                'ai_prompt' => $account->ai_prompt,
                'created_at' => $account->created_at,
                'updated_at' => $account->updated_at,
            ]);
        }

        // 3. Add whatsapp_phone_number_id to conversations
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->foreignId('whatsapp_phone_number_id')->nullable()->after('whatsapp_account_id')
                ->constrained('whatsapp_phone_numbers')->nullOnDelete();
        });

        // Migrate conversation data
        $phoneNumbers = DB::table('whatsapp_phone_numbers')->get();
        foreach ($phoneNumbers as $pn) {
            DB::table('chat_conversations')
                ->where('whatsapp_account_id', $pn->whatsapp_account_id)
                ->whereNull('whatsapp_phone_number_id')
                ->update(['whatsapp_phone_number_id' => $pn->id]);
        }

        // 4. Add whatsapp_phone_number_id to teams
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('whatsapp_phone_number_id')->nullable()->after('whatsapp_account_id')
                ->constrained('whatsapp_phone_numbers')->nullOnDelete();
        });

        // Migrate team data
        foreach ($phoneNumbers as $pn) {
            DB::table('teams')
                ->where('whatsapp_account_id', $pn->whatsapp_account_id)
                ->whereNull('whatsapp_phone_number_id')
                ->update(['whatsapp_phone_number_id' => $pn->id]);
        }

        // 5. Drop old FK columns
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_account_id']);
            $table->dropColumn('whatsapp_account_id');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_account_id']);
            $table->dropColumn('whatsapp_account_id');
        });

        // 6. Remove phone-level fields from whatsapp_accounts
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->dropColumn(['phone_number_id', 'ai_enabled', 'ai_prompt']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            $table->string('phone_number_id')->nullable()->after('name');
            $table->boolean('ai_enabled')->default(true)->after('is_active');
            $table->text('ai_prompt')->nullable()->after('ai_enabled');
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('whatsapp_account_id')->nullable()
                ->constrained('whatsapp_accounts')->nullOnDelete();
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->foreignId('whatsapp_account_id')->nullable()
                ->constrained('whatsapp_accounts')->nullOnDelete();
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_phone_number_id']);
            $table->dropColumn('whatsapp_phone_number_id');
        });

        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_phone_number_id']);
            $table->dropColumn('whatsapp_phone_number_id');
        });

        Schema::dropIfExists('whatsapp_phone_numbers');
    }
};
