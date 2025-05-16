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

class OrderController extends Controller
{
    public function index()
    {
        $vendorId = Auth::id();

        $orders = VendorOrder::with(['orderItems.product', 'orderItems.variation'])
            ->where('vendor_id', $vendorId)
            ->latest()
            ->get();

        if ($orders->isEmpty()) {
            return ApiResponse::error('No orders found', 404);
        }

        return ApiResponse::success($orders, 'Orders retreived successfully');
    }

    public function show($vendorOrderId)
    {
        $vendorId = Auth::id();

        $vendorOrder = VendorOrder::with(['orderItems.product', 'orderItems.variation'])
            ->where('vendor_id', $vendorId)
            ->find($vendorOrderId);

        if (!$vendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        return ApiResponse::success($vendorOrder, 'Vendor Order retreived successfully');
    }

    public function updateStatus(Request $request, $vendorOrderId)

    {
        $vendorId = Auth::id();

        $vendorOrder = VendorOrder::where('vendor_id', $vendorId)->find($vendorOrderId);

        if (!$vendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Invalid status', 422, $validator->errors());
        }

        $vendorOrder->status = $request->status;
        $vendorOrder->save();

        return ApiResponse::success($vendorOrder, 'Vendor Order status updated successfully.');
    }

    public function delete($id)
    {
        $vendorId = Auth::id();

        $order = VendorOrder::where('vendor_id', $vendorId)->find($id);

        if (!$order) {
            return ApiResponse::error('Order not found', 404);
        }

        $order->delete();

        return ApiResponse::success([], 'Order deleted successfully.');
    }


















    /**
     * Create a shipment record for a vendor order.
     */
    public function createShipment(Request $request, $vendorOrderId)
    {
        $vendorId = Auth::id();

        $vendorOrder = VendorOrder::where('vendor_id', $vendorId)->findOrFail($vendorOrderId);

        $request->validate([
            'carrier_id' => 'required|exists:shipping_carriers,id',
            'shipping_address_id' => 'required|exists:shipping_addresses,id',
            'tracking_number' => 'required|string|unique:shipments,tracking_number',
            'service_level' => 'nullable|string|max:50',
            'package_weight' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'insurance_cost' => 'nullable|numeric|min:0',
            'estimated_delivery_date' => 'nullable|date',
        ]);

        Shipment::create([
            'user_id' => $vendorOrder->order->user_id,
            'vendor_id' => $vendorId,
            'vendor_order_id' => $vendorOrderId,
            'carrier_id' => $request->carrier_id,
            'shipping_address_id' => $request->shipping_address_id,
            'tracking_number' => $request->tracking_number,
            'shipping_cost' => $request->shipping_cost ?? 0,
            'insurance_cost' => $request->insurance_cost ?? 0,
            'package_weight' => $request->package_weight,
            'service_level' => $request->service_level,
            'status' => 'label_created',
            'estimated_delivery_date' => $request->estimated_delivery_date,
            'label_created_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Shipment created successfully.');
    }
}