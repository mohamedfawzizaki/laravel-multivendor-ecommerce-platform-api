<?php

namespace App\Http\Controllers\Api\Admin\Shipping;

use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Shipping\ShipmentEvent;
use App\Http\Requests\Shipping\StoreShipmentEventRequest;


class ShipmentEventController extends Controller
{
    public function index()
    {
        $events = ShipmentEvent::with('shipment')->get();
        return ApiResponse::success($events);
    }

    public function store(StoreShipmentEventRequest $request)
    {
        $event = ShipmentEvent::create($request->validated());
        return ApiResponse::success($event, 'Shipment event logged', 201);
    }

    public function show(ShipmentEvent $shipmentEvent)
    {
        return ApiResponse::success($shipmentEvent->load('shipment'));
    }

    public function destroy(ShipmentEvent $shipmentEvent)
    {
        $shipmentEvent->delete();
        return ApiResponse::success(null, 'Shipment event deleted');
    }
}