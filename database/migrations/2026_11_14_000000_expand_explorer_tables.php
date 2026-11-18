<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_logs', function (Blueprint $table): void {
            $table->string('session_id')->index();
            $table->text('user_message');
            $table->longText('bot_reply');
            $table->string('intent_detected')->nullable();
            $table->longText('sparql_query_used')->nullable();
        });

        Schema::table('favorites', function (Blueprint $table): void {
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('session_id')->nullable()->index();
            $table->string('resource_uri', 512);
            $table->string('resource_type', 20);
            $table->unique(['session_id', 'resource_uri']);
        });

        Schema::table('itineraries', function (Blueprint $table): void {
            $table->string('session_id')->nullable()->index();
            $table->string('title')->default('Mall hopping itinerary');
            $table->json('payload')->nullable();
            $table->dateTime('starts_at')->nullable();
        });

        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table): void {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');

        Schema::table('itineraries', function (Blueprint $table): void {
            $table->dropIndex(['session_id']);
            $table->dropColumn(['session_id', 'title', 'payload', 'starts_at']);
        });

        Schema::table('favorites', function (Blueprint $table): void {
            $table->dropUnique(['session_id', 'resource_uri']);
            $table->dropIndex(['session_id']);
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn(['session_id', 'resource_uri', 'resource_type']);
        });

        Schema::table('chat_logs', function (Blueprint $table): void {
            $table->dropIndex(['session_id']);
            $table->dropColumn(['session_id', 'user_message', 'bot_reply', 'intent_detected', 'sparql_query_used']);
        });
    }
};
