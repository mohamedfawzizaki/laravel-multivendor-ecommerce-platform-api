<?php

namespace App\Models;

use InvalidArgumentException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Currency extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'is_base_currency',
        'exchange_rate',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_base_currency' => 'boolean',
        'exchange_rate' => 'decimal:6',
    ];

    // 🔍 Scope: only active currencies
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    // 🏛 Ensure only one base currency is true
    public static function boot()
    {
        parent::boot();

        static::saving(function ($currency) {
            if ($currency->is_base_currency) {
                Currency::where('is_base_currency', true)
                    ->where('code', '!=', $currency->code)
                    ->update(['is_base_currency' => false]);
            }
        });
    }

    // 💱 Convert an amount from this currency to another
    public function convertTo(Currency $targetCurrency, float $amount): float
    {
        // Convert to base first
        $amountInBase = $amount / $this->exchange_rate;
        // Convert to target
        return round($amountInBase * $targetCurrency->exchange_rate, 2);
    }

    // 💲 Format amount in this currency
    public function format(float $amount): string
    {
        return $this->symbol . number_format($amount, 2);
    }

    // 🧠 Static: Get base currency (cached)
    public static function base(): ?Currency
    {
        return Cache::rememberForever('base_currency', function () {
            return self::where('is_base_currency', true)->first();
        });
    }

    // 🔁 Static: Convert between any two currencies
    public static function convert(string $fromCode, string $toCode, float $amount): float
    {
        $from = self::active()->where('code', '=', $fromCode)->first();
        $to = self::active()->where('code', '=',  $toCode)->first();

        if (!$from || !$to) {
            throw new InvalidArgumentException('the currency code is invalid or not active');
        }

        return $from->convertTo($to, $amount);
    }
}


/**
 * * Optional Enhancements
         *     Add a scheduled job to update exchange_rate from an API
         *     Add localization (e.g., thousands/decimal separators)
         *     Add currency rounding logic per currency (some currencies don’t use cents)
 */