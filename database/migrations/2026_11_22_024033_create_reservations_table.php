
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

            // Pemilik reservasi
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Restoran atau toko yang dipesan
            $table->foreignId('store_id')
                ->constrained('stores')
                ->restrictOnDelete();

            // Informasi pemesan
            $table->string('customer_name');
            $table->string('customer_phone', 30);

            // Jadwal reservasi
            $table->dateTime('reservation_at');
            $table->unsignedSmallInteger('party_size');

            $table->text('notes')->nullable();

            // pending, confirmed, rejected,
            // cancelled, completed
            $table->string('status', 30)->default('pending');

            $table->text('admin_notes')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['store_id', 'reservation_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
