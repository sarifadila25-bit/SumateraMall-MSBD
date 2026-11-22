<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type', 10)->default('free'); // free | paid
            $table->integer('price')->default(0);
            $table->integer('stock')->default(0);
            $table->smallInteger('max_per_user')->default(1);
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT vouchers_type_check CHECK (type IN ('free','paid'))");
        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT vouchers_stock_check CHECK (stock >= 0)");
        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT vouchers_price_check CHECK ((type = 'free' AND price = 0) OR (type = 'paid' AND price > 0))");
        DB::statement("ALTER TABLE vouchers ADD CONSTRAINT vouchers_valid_check CHECK (valid_from IS NULL OR valid_until IS NULL OR valid_until > valid_from)");
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};