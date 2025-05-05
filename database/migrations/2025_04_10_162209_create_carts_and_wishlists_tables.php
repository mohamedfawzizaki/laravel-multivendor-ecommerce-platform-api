<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // BIGINT UNSIGNED AUTO_INCREMENT
            $table->char('user_id', 36)->nullable()->comment('UUID format for registered users');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->char('session_id', 36)->nullable()->comment('UUID format for guest sessions');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('set null');
            $table->unsignedSmallInteger('quantity')->default(1)
                ->comment('Must be > 0 and <= 100');
            $table->decimal('price', 10, 2)->comment('Snapshot of price at time of adding');
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies'); // Centralized currency rates/pricing
            $table->string('notes', 500)->nullable()->comment('Special instructions (500 char limit)');
            // Expiry 30 days from now (manual default workaround)
            $table->timestamp('expires_at')->nullable();
            $table->softDeletes(); // Adds deleted_at
            
            $table->timestamps();
            // Indexes
            $table->index('user_id', 'idx_cart_user');
            $table->index('session_id', 'idx_cart_session');
            $table->index('expires_at', 'idx_cart_expiry');
            $table->index('product_id', 'idx_cart_product');
            // Unique constraints
            $table->unique(['user_id', 'product_id', 'variation_id'], 'uk_user_product_variation');
            $table->unique(['session_id', 'product_id', 'variation_id'], 'uk_session_product_variation');
        });

        // Add CHECK constraint via raw SQL (Laravel doesn't support this yet)
        DB::statement("
        ALTER TABLE carts 
        ADD CONSTRAINT chk_user_or_session 
        CHECK (
            (user_id IS NOT NULL AND session_id IS NULL) OR 
            (user_id IS NULL AND session_id IS NOT NULL)
            )
            ");

        // Optional: Set default value for expires_at using raw SQL
        DB::statement("
            ALTER TABLE carts 
            ALTER expires_at 
            SET DEFAULT (CURRENT_TIMESTAMP + INTERVAL 30 DAY)
            ");


        Schema::create('wishlists', function (Blueprint $table) {
            $table->id();
            // User/Session Identification
            $table->char('user_id', 36)->nullable()->comment('UUID for authenticated users');
            $table->char('session_id', 36)->nullable()->comment('UUID for guest sessions');
            // Wishlist Metadata
            $table->string('wishlist_name', 100)->default('Default');
            $table->unsignedBigInteger('product_id');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('set null');
            // User Preferences
            $table->string('notes', 500)->nullable()->comment('Short user notes');
            $table->enum('notify_preferences', ['none', 'discount', 'restock', 'both'])->default('none');
            // Timestamps
            $table->timestamp('expires_at')->nullable()->comment('Auto-expire guest lists');
            $table->timestamps();
            $table->softDeletes();  // For soft delete feature
            // Constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // Unique constraints
            $table->unique(['user_id', 'product_id', 'variation_id'], 'uk_user_product_variation');
            $table->unique(['session_id', 'product_id', 'variation_id'], 'uk_session_product_variation');
            // Indexes
            $table->index('user_id');
            $table->index('session_id');
            $table->index('expires_at');
        });

        DB::statement("
            ALTER TABLE wishlists 
            ADD CONSTRAINT chk_user_or_session 
            CHECK (
                (user_id IS NOT NULL AND session_id IS NULL) OR 
                (user_id IS NULL AND session_id IS NOT NULL)
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
        Schema::dropIfExists('wishlists');
    }
};