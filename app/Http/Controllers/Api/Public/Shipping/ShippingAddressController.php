<?php

namespace App\Http\Controllers\Api\Public\Shipping;

use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Shipping\ShippingAddress;
use App\Http\Requests\Shipping\StoreShippingAddressRequest;
use App\Http\Requests\Shipping\UpdateShippingAddressRequest;

class ShippingAddressController extends Controller
{
    public function index()
    {
        $addresses = ShippingAddress::where('user_id', Auth::id())->get();

        return ApiResponse::success($addresses, "my shipping addresses retreived successfully");
    }

    public function show(string $id)
    {
        $shippingAddress = ShippingAddress::where('user_id', Auth::id())->find($id);

        if (!$shippingAddress) {
            return ApiResponse::error('shipping address not found', 404);
        }

        return ApiResponse::success($shippingAddress, 'shipping address retreived successfully');
    }

    public function store(StoreShippingAddressRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = Auth::id();

        $address = ShippingAddress::create($data);

        return ApiResponse::success($address, 'Shipping address created', 201);
    }

    public function update(UpdateShippingAddressRequest $request, string $id)
    {
        $shippingAddress = ShippingAddress::where('user_id', Auth::id())->find($id);

        if (!$shippingAddress) {
            return ApiResponse::error('shipping address not found', 404);
        }

        $shippingAddress->update($request->validated());

        return ApiResponse::success($shippingAddress, 'Shipping address updated');
    }

    public function destroy(string $id)
    {
        $shippingAddress = ShippingAddress::where('user_id', Auth::id())->find($id);

        if (!$shippingAddress) {
            return ApiResponse::error('shipping address not found', 404);
        }

        $shippingAddress->delete();

        return ApiResponse::success([], 'Shipping address deleted');
    }

    public function restore(string $id)
    {
        $shippingAddress = ShippingAddress::withTrashed()->where('user_id', Auth::id())->find($id);

        if (!$shippingAddress) {
            return ApiResponse::error('shipping address not found', 404);
        }

        $shippingAddress->restore();
        return ApiResponse::success($shippingAddress, 'shipping address restored.');
    }

    protected function authorize(ShippingAddress $shippingAddress)
    {
        return (Auth::check() && ($shippingAddress->user_id == Auth::id() || Auth::user()->role->name == 'admin')) ? true : false;
    }
}