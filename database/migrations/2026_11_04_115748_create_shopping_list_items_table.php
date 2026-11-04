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
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopping_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name', 255);
            $table->string('category_name', 255)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->boolean('checked')->default(false);
            $table->timestamp('checked_at')->nullable();
            $table->decimal('unit_price', 12, 2)->nullable();
            $table->timestamps();

            $table->unique(['shopping_list_id', 'product_id']);
            $table->index(['shopping_list_id', 'checked']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
