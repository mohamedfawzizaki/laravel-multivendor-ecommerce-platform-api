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
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique('unq_brand_name');
            // $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('logo_url')->nullable();
            $table->text('website_url')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique('unq_brand_name');
            $table->text('description')->nullable();
            // $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('category_hierarchy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('child_id')->constrained('categories')->cascadeOnDelete();
            $table->timestamps();

            // Ensure unique parent-child relationships
            $table->unique(['parent_id', 'child_id'], 'unique_parent_child');
        });

        Schema::create('product_statuses', function (Blueprint $table) {
            $table->tinyIncrements('id'); // TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
            $table->string('name', 20)->unique(); // Unique status name
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();

            $table->tinyInteger('status_id')->unsigned()->default(1);
            $table->foreign('status_id')->references('id')->on('product_statuses');

            $table->string('name')->unique();
            // $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_url');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_primary']);
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('variant_name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('stock')->default(0);
            $table->string('sku')->unique();
            $table->json('attributes')->nullable(); // JSON column for attributes (size, color, etc.)
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['product_id', 'sku']); // Ensures SKU uniqueness per product
        });


        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Foreign key to products table
            $table->decimal('discount_price', 10, 2)->default(0.00); // Fixed discount price
            $table->decimal('discount_percentage', 5, 2)->nullable()->check('discount_percentage BETWEEN 0.00 AND 100.00'); // Optional percentage discount
            $table->timestamp('start_date')->nullable(); // Discount start date
            $table->timestamp('end_date')->nullable(); // Discount end date
            $table->timestamps(); // created_at & updated_at
            $table->softDeletes();
        });

        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id(); // Auto-increment primary key
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // FK to products
            $table->uuid('user_id')->nullable(); // Must match UUID type from users table
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete(); // Manual FK setup
            $table->text('review');
            $table->tinyInteger('rating')->unsigned()->check('rating BETWEEN 1 AND 5');
            $table->boolean('verified_purchase')->default(false);
            $table->timestamps();
            $table->softDeletes();

            // Optional indexes
            $table->index(['product_id']);
            $table->index(['user_id']);
            $table->index(['rating']);
        });





        // Schema::create('inventory_change_types', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        //     $table->softDeletes();

        // });


        // Schema::create('inventory_transactions', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        //     $table->softDeletes();

        // });

        // Schema::create('product_inventory_logs', function (Blueprint $table) {
        //     $table->id();
        //     $table->timestamps();
        //     $table->softDeletes();

        // });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_discounts');
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_inventory');
        Schema::dropIfExists('product_images');

        Schema::dropIfExists('products');
        Schema::dropIfExists('product_statuses');

        Schema::dropIfExists('brands');
        Schema::dropIfExists('category_hierarchy');
        Schema::dropIfExists('categories');
        // Schema::dropIfExists('inventory_change_types');
        // Schema::dropIfExists('inventory_transactions');
        // Schema::dropIfExists('product_inventory_logs');
    }
};