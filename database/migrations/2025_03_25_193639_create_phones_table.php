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
        Schema::create('phones', function (Blueprint $table) {
            // Primary key column (auto-incrementing ID)
            $table->id();
            // Foreign key relationship to users table with UUID
            // cascadeOnDelete means phone records will be deleted if the related user is deleted
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            // Phone number column with uniqueness constraint
            // Maximum length of 30 characters to accommodate international formats
            $table->string('phone', 30)->unique();
            // Flag indicating if this is the user's primary phone number
            $table->boolean('is_primary')->default(false);
            // Timestamp when phone was verified (null means unverified)
            $table->timestamp('phone_verified_at')->nullable();
            // 6-digit verification code (nullable when not in verification process)
            $table->string('phone_verification_code', 6)->nullable();
            // Expiration time for verification code (prevents stale codes)
            $table->timestamp('phone_verification_expires_at')->nullable();
            // Standard timestamp columns (created_at and updated_at)
            $table->timestamps();
            // Soft delete column (deleted_at)
            $table->softDeletes();
            // Composite index for faster queries filtering by user_id and phone
            $table->index(['user_id', 'phone'], 'idx_user_id_phone');
            // Composite index for faster queries filtering by user_id and primary status
            $table->index(['user_id', 'is_primary'], 'idx_user_id_is_primary');
            // Composite index for verification code lookups with expiry check
            $table->index(['phone_verification_code', 'phone_verification_expires_at'], 'idx_verification_code_expiry');
            // Unique constraint ensuring a user can have only one primary phone
            // The unique constraint allows multiple false values but only one true value per user
            // $table->unique(['user_id', 'is_primary'], 'unique_user_primary_phone')
            //     ->where('is_primary', 1); // Enforce the constraint only when primary
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('phones');
    }
};