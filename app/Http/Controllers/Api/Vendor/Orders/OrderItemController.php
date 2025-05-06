<?php

namespace App\Http\Controllers\Api\Vendor\Orders;

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

    public function index($vendorOrderId)
    {
        $vendorId = Auth::id();

        $VendorOrder = VendorOrder::where('vendor_id', $vendorId)->find($vendorOrderId);

        if (!$VendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        $orderItems = OrderItem::where('vendor_order_id', $vendorOrderId)
            ->get();

        if ($orderItems->isEmpty()) {
            return ApiResponse::error('No orders found', 404);
        }

        return ApiResponse::success($orderItems, 'Order Items retreived successfully');
    }

    public function show($vendorOrderId, $itemId)
    {
        $vendorId = Auth::id();

        $VendorOrder = VendorOrder::where('vendor_id', $vendorId)->find($vendorOrderId);

        if (!$VendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        $orderItem = OrderItem::where('vendor_order_id', $vendorOrderId)->find($itemId);

        if (!$orderItem) {
            return ApiResponse::error('No orders found', 404);
        }

        return ApiResponse::success($orderItem, 'Order Items retreived successfully');
    }

    public function updateStatus(Request $request, $vendorOrderId, $itemId)
    {
        $vendorId = Auth::id();

        $VendorOrder = VendorOrder::where('vendor_id', $vendorId)->find($vendorOrderId);

        if (!$VendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        $orderItem = OrderItem::where('vendor_order_id', $vendorOrderId)
            ->where('order_id', $VendorOrder->order_id)
            ->find($itemId);

        if (!$orderItem) {
            return ApiResponse::error('Order item not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,fulfilled,shipped,delivered,returned,refunded,cancelled',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Invalid status', 422, $validator->errors());
        }

        $orderItem->status = $request->status;
        $orderItem->save();

        return ApiResponse::success($orderItem, 'Order Item status updated successfully.');
    }

    public function delete($vendorOrderId, $itemId)
    {
        $vendorId = Auth::id();

        $VendorOrder = VendorOrder::where('vendor_id', $vendorId)->find($vendorOrderId);

        if (!$VendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        $orderItem = OrderItem::where('vendor_order_id', $vendorOrderId)
            ->where('order_id', $VendorOrder->order_id)
            ->find($itemId);

        if (!$orderItem) {
            return ApiResponse::error('Order item not found', 404);
        }

        $orderItem->delete();

        return ApiResponse::success([], 'Order Item deleted successfully.');
    }
}