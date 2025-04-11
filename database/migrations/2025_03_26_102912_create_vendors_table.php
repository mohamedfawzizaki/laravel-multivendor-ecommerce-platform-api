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
        Schema::create('vendors', function (Blueprint $table) {
            // Primary Key
            $table->id();

            // Foreign Key with Unique Constraint (One-to-One Relationship)
            $table->foreignUuid('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Business Information
            $table->string('business_name')->nullable();
            $table->string('business_description')->nullable();


            // Media URLs
            $table->string('documentation_url', 512)->nullable();
            $table->string('logo_url', 512)->nullable();

            // Status Management
            $table->enum('status', [
                'PENDING',
                'APPROVED',
                'SUSPENDED',
                'REJECTED'
            ])->default('PENDING');

            // Timestamps
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('status', 'idx_vendor_status');
            $table->index('created_at', 'idx_vendor_created_at');
            $table->fullText(['business_name'], 'ft_vendor_search');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};