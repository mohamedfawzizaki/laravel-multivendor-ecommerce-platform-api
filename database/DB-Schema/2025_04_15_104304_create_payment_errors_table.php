<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_errors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->char('user_id', 26);
            $table->foreignId('order_id')->nullable()->constrained()->onDelete('set null');
            
            // Error details
            $table->enum('error_type', [
                'validation', 'gateway', 'fraud', 'authentication', 'insufficient_funds', 'system', 'timeout', 'unknown'
            ]);
            
            $table->string('error_code', 50);
            $table->text('error_message');
            $table->json('gateway_response')->nullable();
            $table->boolean('is_recoverable')->default(false);
            $table->unsignedTinyInteger('retry_count')->default(0);
            
            $table->timestamps();

            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('order_id');
            $table->index('payment_id');
            $table->index('error_type');
            $table->index('error_code');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_errors');
    }
};