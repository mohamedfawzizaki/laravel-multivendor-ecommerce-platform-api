<?php

namespace App\Http\Controllers\Api\Admin\Shipping;

use App\Models\Shipping\Shipment;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shipping\StoreShipmentRequest;
use App\Http\Requests\Shipping\UpdateShipmentRequest;

class ShipmentController extends Controller
{
    public function index()
    {
        $shipments = Shipment::with(['carrier', 'shippingAddress', 'order'])->get();
        return ApiResponse::success($shipments);
    }

    public function store(StoreShipmentRequest $request)
    {
        $shipment = Shipment::create($request->validated());
        return ApiResponse::success($shipment, 'Shipment created', 201);
    }

    public function show(Shipment $shipment)
    {
        return ApiResponse::success($shipment->load(['carrier', 'shippingAddress', 'order']));
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment)
    {
        $shipment->update($request->validated());
        return ApiResponse::success($shipment, 'Shipment updated');
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();
        return ApiResponse::success(null, 'Shipment deleted');
    }
}