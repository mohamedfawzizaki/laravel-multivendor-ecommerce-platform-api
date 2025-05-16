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

class SubOrderController extends Controller
{
    public function allVendorsSubOrders()
    {
        $vendorOrders = VendorOrder::with(['orderItems.product', 'orderItems.variation'])
            ->latest()
            ->get();

        if ($vendorOrders->isEmpty()) {
            return ApiResponse::error('No vendor orders found', 404);
        }

        return ApiResponse::success($vendorOrders, 'vendor orders retreived successfully');
    }

    public function allSubOrdersForVendor($vendorId)
    {
        $vendorOrders = VendorOrder::with(['orderItems.product', 'orderItems.variation'])
        ->where('vendor_id', $vendorId)    
        ->latest()
            ->get();

        if ($vendorOrders->isEmpty()) {
            return ApiResponse::error('No vendor orders found', 404);
        }

        return ApiResponse::success($vendorOrders, 'vendor orders retreived successfully');
    }

    public function specificSubOrderForVendor($vendorId, $vendorOrderId)
    {
        $vendorOrder = VendorOrder::with(['orderItems.product', 'orderItems.variation'])
            ->where('vendor_id', $vendorId)
            ->find($vendorOrderId);

        if (!$vendorOrder) {
            return ApiResponse::error('Vendor Order not found', 404);
        }

        return ApiResponse::success($vendorOrder, 'Vendor Order retreived successfully');
    }
}