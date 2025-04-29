<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_transaction_id')->nullable()->constrained('payment_transactions')->onDelete('set null');
            
            // Transaction details
            $table->enum('transaction_type', [
                'authorization', 
                'capture', 
                'refund', 
                'void', 
                'chargeback',
                'dispute',
                'adjustment'
            ]);
            
            $table->decimal('amount', 12, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('gateway_transaction_id');
            $table->string('gateway_status');
            $table->string('gateway_response_code')->nullable();
            $table->text('gateway_response')->nullable();
            
            // Status flags
            $table->boolean('is_success')->default(false);
            $table->boolean('requires_action')->default(false);
            $table->string('action_url', 512)->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            $table->string('processed_by', 50)->nullable();
            
            $table->timestamps();

            // Indexes
            $table->index('payment_id');
            $table->index('parent_transaction_id');
            $table->index('gateway_transaction_id');
            $table->index('created_at');
            $table->index('transaction_type');
            $table->index('is_success');
            $table->index('gateway_status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_transactions');
    }
};