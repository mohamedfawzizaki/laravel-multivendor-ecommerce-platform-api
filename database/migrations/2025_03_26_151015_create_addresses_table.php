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

        Schema::create('continents', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('continent_id')->constrained('continents')->restrictOnDelete(); // Prevents deletion if countries exist
            $table->string('name')->unique();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['continent_id', 'name'], 'idx_country_continent_name');
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained('countries')->restrictOnDelete(); // Prevents deletion if cities exist
            $table->string('name');
            $table->unique(['name', 'country_id'], 'unique_city_name_per_country');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['country_id', 'name'], 'idx_city_country_name');
        });

        Schema::create('user_address', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete(); // Deletes address when user is deleted
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete(); // Deletes address when city is deleted
            $table->timestamps();
            $table->softDeletes();
            $table->primary(['user_id', 'city_id'], 'user_address_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_address');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('continents');
    }
};