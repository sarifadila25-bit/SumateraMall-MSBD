
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table): void {
            $table->id();

            // Admin yang membuat voucher
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Voucher bisa berlaku untuk mall atau toko tertentu
            $table->foreignId('mall_id')
                ->nullable()
                ->constrained('malls')
                ->nullOnDelete();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();

            // fixed = potongan nominal, percentage = persentase
            $table->string('discount_type', 20);
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount', 12, 2)->nullable();
            $table->decimal('minimum_purchase', 12, 2)->default(0);

            // Harga 0 berarti voucher gratis
            $table->decimal('price', 12, 2)->default(0);

            // Kuota null berarti tidak terbatas
            $table->unsignedInteger('quota')->nullable();
            $table->unsignedInteger('claimed_count')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('is_active')->default(true);

            $table->text('terms')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
