<?php

namespace App\Http\Controllers\Api\Admin\Shipping;

use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Shipping\ShippingCarrier;
use App\Http\Requests\Shipping\StoreShippingCarrierRequest;
use App\Http\Requests\Shipping\UpdateShippingCarrierRequest;


class ShippingCarrierController extends Controller
{
    public function index()
    {
        $carriers = ShippingCarrier::get();

        return ApiResponse::success($carriers, 'Shipping carriers retreived successfully.');
    }

    public function store(StoreShippingCarrierRequest $request)
    {
        $data = $request->validated();

        $carrier = ShippingCarrier::create([
            'vendor_id' => $data['vendor_id'] ?? null,
            'code' => $data['code'],
            'name' => $data['name'],
            'tracking_url_format' => $data['tracking_url_format'],
            'customer_service_phone' => $data['customer_service_phone'],
            'customer_service_email' => $data['customer_service_email'],
            'website_url' => $data['website_url'],
            'is_active' => $data['is_active'] ?? true,
            'service_levels' => $data['service_levels'] ?? null,
        ]);

        return ApiResponse::success($carrier, 'Shipping carrier created successfully.', 201);
    }

    public function show(string $id)
    {
        $carrier = ShippingCarrier::find($id);

        if (!$carrier) {
            return ApiResponse::error(message: 'shipping carrier not found', status: 404);
        }

        return ApiResponse::success($carrier);
    }

    public function update(UpdateShippingCarrierRequest $request, string $id)
    {

        $carrier = ShippingCarrier::find($id);

        if (!$carrier) {
            return ApiResponse::error(message: 'shipping carrier not found', status: 404);
        }
        
        $carrier->update($request->validated());
        
        return ApiResponse::success($carrier, 'Shipping carrier updated');
    }

    public function destroy(string $id)
    {
        $carrier = ShippingCarrier::find($id);

        if (!$carrier) {
            return ApiResponse::error(message: 'shipping carrier not found', status: 404);
        }
        
        $carrier->delete();
        
        return ApiResponse::success(null, 'Shipping carrier deleted');
    }

    public function restore(string $id)
    {
        $carrier = ShippingCarrier::withTrashed()->find($id);

        if (!$carrier) {
            return ApiResponse::error('shipping carrier not found', 404);
        }

        $carrier->restore();
        return ApiResponse::success($carrier, 'shipping carrier restored.');
    }
}