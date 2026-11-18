
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel provinsi
        Schema::create('provinces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('resource_uri', 512)->unique();
            $table->timestamps();
        });

        // 2. Tabel kota
        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('province_id')
                ->constrained('provinces')
                ->restrictOnDelete();
            $table->string('name');
            $table->string('resource_uri', 512)->unique();
            $table->timestamps();
        });

        // 3. Tabel mall
        Schema::create('malls', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('city_id')
                ->constrained('cities')
                ->restrictOnDelete();

            $table->string('name');
            $table->text('address')->nullable();
            $table->string('opening_hours')->nullable();
            $table->unsignedInteger('floors')->nullable();
            $table->string('category')->nullable();
            $table->text('area')->nullable();
            $table->text('website')->nullable();
            $table->text('description')->nullable();

            $table->string('source_namespace', 20);
            $table->string('resource_uri', 512)->unique();

            $table->timestamps();
        });

        // 4. Tabel toko
        Schema::create('stores', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mall_id')
                ->constrained('malls')
                ->restrictOnDelete();

            $table->string('name');
            $table->text('category_text')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('location')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->string('opening_hours')->nullable();
            $table->string('phone', 50)->nullable();
            $table->decimal('rating', 3, 2)->nullable();

            $table->string('source_namespace', 20);
            $table->string('resource_uri', 512)->unique();

            $table->timestamps();
        });

        // 5. Tabel kategori
        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('resource_uri', 512)->unique();
            $table->timestamps();
        });
        // 6. Tabel penghubung toko dan kategori
        Schema::create('store_categories', function (Blueprint $table): void {
            $table->foreignId('store_id')
                ->constrained('stores')
                ->cascadeOnDelete();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->primary(['store_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_categories');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('malls');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('provinces');
    }
};
