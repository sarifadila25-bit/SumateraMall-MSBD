
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_vouchers', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('voucher_id')
                ->constrained('vouchers')
                ->restrictOnDelete();

            // Kode unik untuk setiap klaim user
            $table->string('claim_code')->unique();

            $table->string('status', 30)->default('active');

            // Simpan harga saat voucher diklaim
            $table->decimal('purchase_price', 12, 2)->default(0);

            // Simulasi pembayaran
            $table->string('payment_status', 30)->default('not_required');
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('used_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['voucher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vouchers');
    }
};
