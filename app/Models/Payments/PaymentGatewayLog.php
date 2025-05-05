<?php

namespace App\Models\Payments;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentGatewayLog extends Model
{
public $timestamps = false;
const CREATED_AT = 'created_at';

protected $fillable = [
'payment_id',
'transaction_id',
'direction',
'url',
'method',
'status_code',
'headers',
'body',
'response_time_ms',
];

protected $casts = [
'headers' => 'array',
'response_time_ms' => 'integer',
'status_code' => 'integer',
'created_at' => 'datetime',
];

public function payment(): BelongsTo
{
return $this->belongsTo(OrderPayment::class);
}

public function transaction(): BelongsTo
{
return $this->belongsTo(PaymentTransaction::class, 'transaction_id');
}
}