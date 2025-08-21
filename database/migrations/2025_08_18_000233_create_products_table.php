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
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->decimal('price', 10, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0); // مثال: 12.50%
            $table->decimal('rating', 3, 2)->default(0); // مثال: 4.95
            $table->integer('stock');
            $table->string('brand')->nullable();
            $table->string('sku')->unique();
            $table->integer('weight')->nullable();

            //dimensions in one column
            $table->json('dimensions')->nullable();

            $table->string('warranty_information')->nullable();
            $table->string('shipping_information')->nullable();
            $table->string('availability_status')->nullable();
            $table->string('return_policy')->nullable();
            $table->integer('minimum_order_quantity')->default(1);

            // meta
            $table->string('barcode')->nullable();
            $table->string('qr_code')->nullable();
            $table->timestamps();

            //image
            $table->string('thumbnail')->nullable();
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
