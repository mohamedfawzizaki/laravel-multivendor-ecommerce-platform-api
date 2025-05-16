<?php

namespace App\Http\Controllers\Api\Vendor\Shipping;

use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Shipping\ShippingAddress;
use App\Http\Requests\Shipping\StoreShippingAddressRequest;
use App\Http\Requests\Shipping\UpdateShippingAddressRequest;

class ShippingAddressController extends Controller
{
    public function show(string $id)
    {
        $shippingAddress = ShippingAddress::where('user_id', Auth::id())->find($id);

        if (!$shippingAddress) {
            return ApiResponse::error('shipping address not found', 404);
        }

        return ApiResponse::success($shippingAddress, 'shipping address retreived successfully');
    }

    public function shippingAddressesOfAnUser(string $userID)
    {
        $shippingAddresses = ShippingAddress::where('user_id', $userID)->get();

        if ($shippingAddresses->isEmpty()) {
            return ApiResponse::error('shipping addresses not found', 404);
        }

        return ApiResponse::success($shippingAddresses, 'shipping addresses of user retreived successfully');
    }

    public function aShippingAddressOfAnUser(string $shippingAddressId, string $userID)
    {
        $shippingAddress = ShippingAddress::where('user_id', $userID)->find($shippingAddressId);

        if (!$shippingAddress) {
            return ApiResponse::error('shipping address not found', 404);
        }

        return ApiResponse::success($shippingAddress, 'shipping address of user retreived successfully');
    }
}