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
        /**
         * Table: currencies
         * 
         * This table stores all supported currencies in the system.
         * It includes ISO currency codes, full names, symbols, exchange rates, and status flags.
         * 
         * Key Use Cases:
         * - Allows multi-currency product pricing and transactions.
         * - Supports currency conversion based on real-time or manual exchange rates.
         * - Provides a base currency for financial normalization and reporting.
         * - auto-sync exchange rates from an API.
         * Optional Enhancements
         *     Add a scheduled job to update exchange_rate from an API
         *     Add localization (e.g., thousands/decimal separators)
         *     Add currency rounding logic per currency (some currencies don’t use cents)
         * Fields:
         * - code: ISO 4217 currency code (e.g., USD, EUR, INR).
         * - name: Full name of the currency for display purposes.
         * - symbol: Common currency symbol (e.g., $, €, ₹).
         * - is_active: Toggle to enable/disable this currency for use.
         * - is_base_currency: Marks this as the base currency (only one should be true).
         * - exchange_rate: Rate to convert this currency to the base currency.
         */
        Schema::create('currencies', function (Blueprint $table) {
            $table->string('code', 10)->primary();         // ISO 4217 code: USD, EUR, etc.
            $table->string('name', 100);                   // Currency full name
            $table->string('symbol', 10);                  // Currency symbol: $, €, etc.
            $table->boolean('is_active')->default(true);   // Indicates if this currency is enabled
            $table->boolean('is_base_currency')->default(false); // True if this is the system's base currency
            $table->decimal('exchange_rate', 15, 6)->default(1.000000); // Exchange rate relative to base currency
            $table->timestamps();                          // Tracks creation and update time
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};