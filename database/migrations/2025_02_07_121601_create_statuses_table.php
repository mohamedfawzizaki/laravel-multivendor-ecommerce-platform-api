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
        Schema::create('statuses', function (Blueprint $table) {
            // Primary key: Unique identifier for each status
            $table->id();
            // Status name (e.g., 'active', 'inactive', 'banned') - must be unique
            $table->string('name', 20)->unique('uq_status_name');
            // Optional description of the status
            $table->text('description')->nullable();
            // Timestamp when the status was last updated
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};