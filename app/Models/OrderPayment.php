<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    // Defining the table name if it's not the plural of the model name
    protected $table = 'order_payments';

    // Defining the fillable fields (columns that can be mass-assigned)
    protected $fillable = [
        'order_id', 'method', 'status', 'transaction_id', 'amount', 'currency_code', 'processed_at', 'gateway_response'
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }

    // Optional: You could create a custom accessor to format the payment amount with the currency symbol
    public function getFormattedAmountAttribute()
    {
        $currency = $this->currency; // Access the related currency
        return $currency ? "{$currency->symbol} " . number_format($this->amount, 2) : number_format($this->amount, 2);
    }

    // Helper method to check if the payment is successful
    public function isPaid()
    {
        return $this->status === 'paid';
    }

    // Other methods for business logic could go here
}