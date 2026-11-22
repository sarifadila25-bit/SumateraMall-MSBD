<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('voucher_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_voucher_id')->nullable()->constrained('user_vouchers')->nullOnDelete();
            $table->string('type', 10);                       // claim | purchase
            $table->integer('amount')->default(0);
            $table->string('payment_status', 10)->default('pending'); // pending | paid | failed
            $table->string('payment_ref', 60)->nullable()->unique();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('voucher_id');
        });

        DB::statement("ALTER TABLE voucher_transactions ADD CONSTRAINT voucher_transactions_type_check CHECK (type IN ('claim','purchase'))");
        DB::statement("ALTER TABLE voucher_transactions ADD CONSTRAINT voucher_transactions_status_check CHECK (payment_status IN ('pending','paid','failed'))");
        DB::statement("ALTER TABLE voucher_transactions ADD CONSTRAINT voucher_transactions_amount_check CHECK (amount >= 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_transactions');
    }
};