<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_name')->unique();
            $table->timestamps();
        });
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string("code")->unique();
            $table->foreignId('brand_id')->constrained('brands');
            $table->string("name");
            $table->string("description");
            $table->decimal('price', 8, 2);
            $table->integer('warranty')->default(0);
            $table->foreignId('category_id')->nullable()->constrained('categories');
            $table->foreignId('second_category_id')->nullable()->constrained('categories');
            $table->foreignId('catalog_id')->nullable()->constrained('categories');
            $table->integer('availability')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
        Schema::dropIfExists('categories');
    }
};
