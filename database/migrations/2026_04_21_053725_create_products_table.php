<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('in_stock')->default(true);
            $table->float('rating')->default(0.0);
            $table->timestamps();

            // Индексы для производительности
            $table->index('price');
            $table->index('category_id');
            $table->index('in_stock');
            $table->index('rating');
            $table->index('created_at');
            $table->fullText('name'); // MySQL 5.6+ / PostgreSQL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
