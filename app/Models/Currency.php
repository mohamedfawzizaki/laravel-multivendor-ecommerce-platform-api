<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    // Defining the table name if it's not the plural of the model name
    protected $table = 'currencies';

    // The primary key is the 'code' column, which is a string
    protected $primaryKey = 'code';

    // Defining the fillable fields (columns that can be mass-assigned)
    protected $fillable = [
        'code', 'name', 'symbol', 'exchange_rate'
    ];

    // You could add helper methods if needed, for example to format the exchange rate
    public function formatExchangeRate()
    {
        return number_format($this->exchange_rate, 6);
    }
}