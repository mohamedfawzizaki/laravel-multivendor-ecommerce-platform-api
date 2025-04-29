<?php

namespace App\Http\Controllers\Api\Vendor\Orders;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Shipping\Shipment;
use App\Models\Orders\VendorOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class VendorOrderController extends Controller
{
    /**
     * Display a listing of vendor's orders.
     */
    public function index(Request $request)
    {
        $vendorId = Auth::id();

        $orders = VendorOrder::where('vendor_id', $vendorId)
            ->when($request->status, function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->latest()
            ->paginate(20);

        return view('vendor.orders.index', compact('orders'));
    }

    /**
     * Display the specified order details.
     */
    public function show($id)
    {
        $vendorId = Auth::id();

        $order = VendorOrder::with(['orderItems.product', 'orderItems.variation'])
            ->where('vendor_id', $vendorId)
            ->findOrFail($id);

        return view('vendor.orders.show', compact('order'));
    }

    /**
     * Update the vendor order status (e.g., mark as shipped).
     */
    public function updateStatus(Request $request, $id)
    {
        $vendorId = Auth::id();

        $order = VendorOrder::where('vendor_id', $vendorId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order->status = $request->status;
        $order->save();

        return redirect()->back()->with('success', 'Order status updated successfully.');
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