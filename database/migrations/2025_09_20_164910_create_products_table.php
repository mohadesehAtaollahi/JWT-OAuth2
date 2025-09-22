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
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('stock');
            $table->foreignId('brand_id')->nullable()->constrained('brands')->onDelete('set null');
            $table->string('sku', 255)->unique();
            $table->integer('weight')->nullable();


            $table->string('warranty_information', 255)->nullable();
            $table->string('shipping_information', 255)->nullable();
            $table->string('availability_status', 50)->nullable();
            $table->string('return_policy', 255)->nullable();
            $table->integer('minimum_order_quantity')->default(1);

            // meta
            $table->string('barcode', 255)->nullable();
            $table->string('qr_code', 1000)->nullable();
            $table->timestamps();

            //image
            $table->string('thumbnail', 1000)->nullable();

            $table->index('sku');
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
