<?php

namespace App\Http\Controllers\Api\Public\Orders;

use Exception;
use App\Models\Cart;
use App\Enums\OrderStatus;
use Illuminate\Support\Str;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Orders\OrderItem;
use Illuminate\Http\JsonResponse;
use App\Models\Orders\VendorOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Models\Orders\OrderPayment;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Orders\OrderService;
use App\Http\Requests\PaginateRequest;
use App\Services\Orders\PaymentHandler;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class OrderController extends Controller
{
    /**
     * Constructor to inject the OrderService dependency.
     *
     * @param OrderService $orderService The service responsible for order-related operations.
     */
    public function __construct(protected OrderService $orderService) {}

    public function index()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->with('vendorOrders')
            ->latest()
            ->get();

        if ($orders->isEmpty()) {
            return ApiResponse::error('No orders found', 404);
        }

        return ApiResponse::success($orders, 'Order retreived successfully');
    }

    public function show($orderId)
    {
        $order = Order::with([
            'vendorOrders.orderItems.product',
            'vendorOrders.orderItems.variation',
        ])
        ->where('user_id', Auth::id())
        ->find($orderId);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        return ApiResponse::success($order, 'Order retreived successfully');
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $order = $this->orderService->createOrderFromCart($user);

        return ApiResponse::success($order, 'order placed successfully', 201);
    }

    public function cancel(string $orderID)
    {
        $order = Order::find($orderID);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $user = Auth::user();

        if ($order->user_id != $user->id) {
            return ApiResponse::error('You do not have permission to cancel this order', 403);
        }

        if ($order->cancel()) {
            return ApiResponse::success([], 'Order cancelled successfully');
        }
        return ApiResponse::error(message: "Failed to cancel order, order is " . $order->status, status: 500);
    }

    public function process(string $orderID)
    {
        $order = Order::find($orderID);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $user = Auth::user();

        if ($order->user_id != $user->id) {
            return ApiResponse::error('You do not have permission to cancel this order', 403);
        }

        if ($order->status->value == 'cancelled') {
            $order->status = OrderStatus::PENDING->value;
            $order->save();
            return ApiResponse::success($order, 'Order is pending now');
        }

        return ApiResponse::error(message: "order already not cancelled, order is " . $order->status, status: 500);
    }
}