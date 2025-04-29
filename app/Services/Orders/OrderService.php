<?php

namespace App\Services\Orders;

use App\Models\Orders\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Repositories\Repos\Orders\OrderRepository;

class OrderService
{
    public function __construct(private Order $order) {}

    
}