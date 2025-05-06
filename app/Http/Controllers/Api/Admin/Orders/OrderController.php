<?php

namespace App\Http\Controllers\Api\Admin\Orders;

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

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['orderItems.product', 'orderItems.variation'])
            ->latest()
            ->get();

        if ($orders->isEmpty()) {
            return ApiResponse::error('No orders found', 404);
        }

        return ApiResponse::success($orders, 'Orders retreived successfully');
    }

    public function show($id)
    {
        $order = Order::with(['orderItems.product', 'orderItems.variation'])->find($id);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        return ApiResponse::success($order, 'Order retreived successfully');
    }

    public function delete($id)
    {
        $order = Order::find($id);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $order->delete();

        return ApiResponse::success([], 'Order deleted successfully.');
    }
}