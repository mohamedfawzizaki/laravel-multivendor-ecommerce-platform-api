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
        Schema::create('continent', function (Blueprint $table) {
            $table->string('name');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'name'], 'idx_user_id_city_name');
            $table->timestamps();
        });

        Schema::create('countries', function (Blueprint $table) {
            $table->string('name');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'name'], 'idx_user_id_city_name');
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->string('name');
            $table->string('address_line');
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['user_id', 'name', 'address_line'], 'idx_user_id_city_name');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};