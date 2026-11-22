<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->string('reservation_code', 30)->unique();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->unsignedSmallInteger('guest_count');
            $table->string('contact_name');
            $table->string('contact_phone', 30);
            $table->text('special_requests')->nullable();

            $table->string('status', 20)->default('pending'); // pending, confirmed, rejected, cancelled
            $table->text('cancellation_reason')->nullable();
            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['store_id', 'reservation_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};