<?php

namespace App\Http\Controllers\Api\Public\Orders;

use Illuminate\Support\Str;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Orders\OrderItem;
use App\Models\Shipping\Shipment;
use App\Models\Orders\VendorOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderItemController extends Controller
{

    public function index($orderId)
    {
        $userId = Auth::id();

        $order = Order::where('user_id', $userId)->find($orderId);

        if (!$order) {
            return ApiResponse::error('User Order not found', 404);
        }

        $orderItems = OrderItem::where('order_id', $order->id)->get();

        if ($orderItems->isEmpty()) {
            return ApiResponse::error('No orders found', 404);
        }

        return ApiResponse::success($orderItems, 'Order Items retreived successfully');
    }

    public function show($orderId, $itemId)
    {
        $order = Order::where('user_id', Auth::id())->find($orderId);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $orderItem = OrderItem::where('order_id', $orderId)->find($itemId);

        if (!$orderItem) {
            return ApiResponse::error('Order Item not found', 404);
        }

        return ApiResponse::success($orderItem, 'Order Item retreived successfully');
    }
}