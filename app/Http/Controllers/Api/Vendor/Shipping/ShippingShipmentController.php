<?php

namespace App\Http\Controllers\Api\Vendor\Shipping;

use Exception;
use App\Models\Orders\Order;
use App\Models\Shipping\Shipment;
use App\Models\Orders\VendorOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Shipping\ShippingAddress;
use App\Models\Shipping\ShippingCarrier;
use App\Http\Requests\Shipping\StoreShipmentRequest;
use App\Http\Requests\Shipping\UpdateShipmentRequest;

class ShippingShipmentController extends Controller
{
    public function allShipmentsOfAVendor()
    {
        $shipments = Shipment::where('vendor_id', Auth::user()->id)->get();

        return ApiResponse::success($shipments, 'Shipments retreived successfully.');
    }

    public function allShipmentsOfAVendorOrder(string $vendorOrderId)
    {
        $shipments = Shipment::where('vendor_id', Auth::user()->id)
            ->where('vendor_order_id',  $vendorOrderId)
            ->get();

        return ApiResponse::success($shipments, 'Shipments retreived successfully.');
    }

    public function allShipmentsOfAnUser(string $userId)
    {
        $shipments = Shipment::where('vendor_id', Auth::user()->id)
            ->where('user_id',  $userId)
            ->get();

        return ApiResponse::success($shipments, 'Shipments retreived successfully.');
    }

    public function specificShipment(string $shipmentId)
    {
        $shipment = Shipment::where('vendor_id', Auth::user()->id)->find($shipmentId);

        if (!$shipment) {
            return ApiResponse::error(message: 'Shipment not found', status: 404);
        }

        return ApiResponse::success($shipment);
    }

    public function specificShipmentOfAnUser(string $shipmentId, string $userId)
    {
        $shipment = Shipment::where('vendor_id', Auth::user()->id)
            ->where('user_id', $userId)
            ->find($shipmentId);

        if (!$shipment) {
            return ApiResponse::error(message: 'Shipment not found', status: 404);
        }

        return ApiResponse::success($shipment);
    }

    public function store(StoreShipmentRequest $request)
    {
        try {
            $data = $request->validated();

            // DB::beginTransaction();

            $vendorOrder = VendorOrder::where('vendor_id', Auth::id())->find($data['vendor_order_id']);

            if (!$vendorOrder) {
                return ApiResponse::error(message: 'Vendor Order not found', status: 404);
            }

            $mainOrder = Order::find($vendorOrder->order_id);

            $user = $mainOrder->user;

            $shippingAddress = ShippingAddress::where('user_id', $user->id)->first();

            if (!$shippingAddress) {
                return ApiResponse::error(message: 'Shipping Address not found', status: 404);
            }

            $carrier = ShippingCarrier::where('vendor_id', Auth::id())->find($data['carrier_id']);

            if (!$carrier) {
                return ApiResponse::error(message: 'Carrier not found', status: 404);
            }

            // $orderPayment = Payment::where('order_id', $mainOrder->id)->first();

            // if (!$orderPayment) {
            //     return ApiResponse::error(message: 'Order Payment not found, Order is not paid yet', status: 404);
            // }

            // if ($orderPayment->payment_status != 'paid') {
            //     return ApiResponse::error(message: 'Order is not paid', status: 400);
            // }

            $existingVendorOrder = Shipment::where('vendor_order_id', $vendorOrder->id)->first();

            if ($existingVendorOrder) {
                return ApiResponse::error(message: 'Shipment already exists for this vendor order', status: 400);
            }

            $shipment = Shipment::create([
                'user_id'             => $user->id,
                'order_id'            => $mainOrder->id,
                'vendor_id'           => Auth::user()->id,
                'vendor_order_id'     => $vendorOrder->id,
                'carrier_id'          => $carrier->id,
                'shipping_address_id' => $shippingAddress->id,
                'status'              => 'pending',
                'tracking_number'     => Shipment::generateUniqueTrackingNumber(),
                'shipping_cost'       => $data['shipping_cost'],
                'insurance_cost'      => $data['insurance_cost'] ?? null,
                'package_weight'      => $data['package_weight'] ?? null,
                'service_level'       => $data['service_level'] ?? null,
                'estimated_delivery_date' => $data['estimated_delivery_date'] ?? null,
                'out_for_delivery_at'     => $data['out_for_delivery_at'] ?? null,
            ]);

            // DB::commit();
            return ApiResponse::success($shipment, 'Shipment created successfully.', 201);
        } catch (Exception $e) {
            // DB::rollBack();
            Log::error($e->getMessage());
            return ApiResponse::error(message: 'shipment creation failed', status: 500);
        }
    }

    public function update(UpdateShipmentRequest $request, string $shipmintId)
    {
        try {
            $shipment = Shipment::where('vendor_id', Auth::id())->find($shipmintId);

            if (!$shipment) {
                return ApiResponse::error(message: 'Shipment not found', status: 404);
            }

            // if (!$this->authorize($shipment)) {
            //     return ApiResponse::error(message: 'You do not own this Shipment', status: 403);
            // }

            $vendorOrder = VendorOrder::find($shipment->vendor_order_id);
            $data = $request->validated();

            DB::beginTransaction();

            if (isset($data['status'])) {
                switch ($data['status']) {
                    case 'shipped':
                        $shipment->update(['shipped_at' => now()]);
                        $vendorOrder->update(['status' => 'shipped']);
                        break;
                    case 'delivered':
                        $shipment->update(['delivered' => now()]);
                        $vendorOrder->update(['status' => 'delivered']);
                        break;
                }
            }

            $shipment->update($data);

            DB::commit();
            return ApiResponse::success($shipment, 'Shipping shipment updated');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getMessage());
            return ApiResponse::error(message: 'shipment update failed', status: 500);
        }
    }

    public function delete(string $shipmintId)
    {
        $shipment = Shipment::where('vendor_id', Auth::id())->find($shipmintId);
        
        if (!$shipment) {
            return ApiResponse::error(message: 'Shipment not found', status: 404);
        }

        // if (!$this->authorize($shipment)) {
        //     return ApiResponse::error(message: 'You do not own this Shipment', status: 403);
        // }

        $shipment->delete();
        
        return ApiResponse::success(null, 'Shipping shipment deleted');
    }
    
    public function restore(string $id)
    {
        $shipment = Shipment::withTrashed()->where('vendor_id', Auth::id())->find($shipmintId);

        if (!$shipment) {
            return ApiResponse::error('Shipment not found', 404);
        }

        if (!$this->authorize($shipment)) {
            return ApiResponse::error(message: 'You do not own this Shipment', status: 403);
        }

        $shipment->restore();

        return ApiResponse::success($shipment, 'Shipment restored.');
    }

    protected function authorize(Shipment $shippingShipment)
    {
        return (Auth::check() && $shippingShipment->vendor_id !== Auth::id()) ? false : true;
    }
}