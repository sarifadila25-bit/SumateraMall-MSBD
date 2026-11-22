<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')
                ->constrained('reservations')
                ->cascadeOnDelete();
            $table->foreignId('changed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['reservation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_status_logs');
    }
};