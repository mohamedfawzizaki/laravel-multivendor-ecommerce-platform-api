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
        Schema::create('users', function (Blueprint $table) {
            // Primary key:  
            $table->uuid('id')->primary();
            // Foreign key: Role ID (References `roles` table)
            // Restricts deletion if referenced in this table
            $table->foreignId('role_id')->constrained('roles')->restrictOnDelete();
            // Foreign key: Status ID (References `statuses` table)
            // Restricts deletion if referenced in this table
            $table->foreignId('status_id')->constrained('statuses')->restrictOnDelete();
            // Unique name for the user
            $table->string('name')->unique('uq_name');
            // Unique email address used for authentication
            $table->string('email')->unique('uq_email');
            // Hashed password for security
            $table->text('password');
            // Timestamp when email was verified (NULL if not verified)
            $table->timestamp('email_verified_at')->nullable();
            // 6-digit OTP code for email verification (NULL if not generated)
            $table->string('email_verification_code', 6)->nullable();
            $table->string('email_verification_token')->nullable();
            // Expiration timestamp for the OTP verification code (NULL if not set)
            $table->timestamp('email_verification_expires_at')->nullable();
            // Laravel's built-in "remember me" token for authentication
            $table->rememberToken();
            // Timestamp of the user's last login (NULL if never logged in)
            $table->timestamp('last_login_at')->nullable();
            // Laravel's default timestamps: `created_at` and `updated_at`
            $table->timestamps();
            // soft delete
            $table->softDeletes();
            // Indexes for optimized queries:
            // - Role filtering performance
            $table->index(['role_id'], 'idx_role_id');
            // - Status filtering performance
            $table->index(['status_id'], 'idx_status_id');
            // - Sorting/filtering by last login timestamp
            $table->index(['last_login_at'], 'idx_last_login_at');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignUuid('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};