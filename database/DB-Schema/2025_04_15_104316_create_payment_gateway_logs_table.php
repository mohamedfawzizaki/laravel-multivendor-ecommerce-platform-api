<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');
            
            // Request/response details
            $table->enum('direction', ['request', 'response']);
            $table->string('url', 512);
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->json('headers')->nullable();
            $table->longText('body')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('payment_id');
            $table->index('transaction_id');
            $table->index('created_at');
            $table->index('direction');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};