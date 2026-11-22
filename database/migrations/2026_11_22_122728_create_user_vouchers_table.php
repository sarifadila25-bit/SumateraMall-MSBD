<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_vouchers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained()->restrictOnDelete();
            $table->string('status', 10)->default('active'); // active | used | expired
            $table->timestamp('claimed_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('voucher_id');
        });

        DB::statement("ALTER TABLE user_vouchers ADD CONSTRAINT user_vouchers_status_check CHECK (status IN ('active','used','expired'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vouchers');
    }
};