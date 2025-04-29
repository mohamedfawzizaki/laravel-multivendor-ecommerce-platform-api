<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Cart;
use Illuminate\Support\Str;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\ProductVariation;
use App\Http\Requests\CartAndWishlist\StoreCartRequest;
use App\Http\Requests\CartAndWishlist\UpdateCartRequest;
use App\Models\ProductsWarehousesManagement\WarehouseInventory;

class CartController extends Controller
{
    public function __construct()
    {
        // Make sure guests have a session_id
        if (!session()->has('session_id')) {
            session(['session_id' => (string) Str::uuid()]);
        }
    }

    /**
     * Display all cart items for the current user/session.
     */
    public function index()
    {
        $query = Cart::query();

        if (Auth::check()) {
            $query->where('user_id', Auth::user()->id);
        } else {
            $query->where('session_id', session('session_id'));
        }

        $cartItems = $query->whereNull('deleted_at')->get();

        return ApiResponse::success($cartItems);
    }

    public function show(string $id)
    {
        $cartItem = Cart::with(['product', 'variation'])
            ->where(function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->where('session_id', session('session_id'));
                }
            })
            ->find($id);

        if (!$cartItem) {
            return ApiResponse::error('Cart item not found or not accessible', 404);
        }

        return ApiResponse::success($cartItem);
    }

    /**
     * Add a new item to the cart.
     */
    public function store(StoreCartRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            // $expirationDays = config('cart.expiration_days', 30);
            $expirationDays = 30;

            // Verify product exists
            $product = Product::find($data['product_id']);
            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            // Handle variation if provided
            $variation = null;
            if (isset($data['variation_id'])) {
                $variation = ProductVariation::find($data['variation_id']);
                if (!$variation) {
                    return ApiResponse::error('Product variation not found', 404);
                }
                
                // Verify variation belongs to product
                if ($variation->product_id != $product->id) {
                    return ApiResponse::error('This variation does not belong to the specified product', 422);
                }
            }
            // Check warehouse inventory
            $itemInWarehouse = $variation ?
                WarehouseInventory::where('variation_id', $data['variation_id'])
                ->first() :
                WarehouseInventory::where('product_id', $data['product_id'])
                ->whereNull('variation_id')
                ->first();

            if (!$itemInWarehouse) {
                return ApiResponse::error('Requested item is not available in stock.', 422);
            }

            $availableQuantity = $itemInWarehouse->quantity_on_hand;

            if ($availableQuantity < $data['quantity']) {
                return ApiResponse::error('Requested quantity is not available in stock, available : '.$availableQuantity, 422);
            }

            $cartData = [
                'product_id' => $product->id,
                'variation_id' => $variation?->id,
                'quantity' => $data['quantity'],
                'price' => $variation ? $variation->price : $product->base_price,
                'currency_code' => $product->currency_code ?? 'USD',
                'notes' => $data['notes'] ?? null,
                'expires_at' => now()->addDays($expirationDays),
            ];

            // Handle user/session
            if (Auth::check()) {
                $cartData['user_id'] = Auth::id();
            } else {
                $sessionId = session('session_id');
                if (!$sessionId) {
                    $sessionId = Str::uuid()->toString();
                    session(['session_id' => $sessionId]);
                }
                $cartData['session_id'] = $sessionId;
            }

            // Check for existing cart item
            $existing = Cart::where(function ($query) use ($cartData) {
                if (isset($cartData['user_id'])) {
                    $query->where('user_id', $cartData['user_id']);
                } else {
                    $query->where('session_id', $cartData['session_id']);
                }
            })
                ->where('product_id', $cartData['product_id'])
                ->where('variation_id', $cartData['variation_id'])
                ->first();

            if ($existing) {
                $newTotalQuantity = $existing->quantity + $cartData['quantity'];

                if ($availableQuantity < $newTotalQuantity) {
                    return ApiResponse::error('Not enough stock for the updated quantity, available : '.$availableQuantity, 422);
                }

                $existing->quantity = $newTotalQuantity;
                $existing->save();

                return ApiResponse::success($existing, 'Cart item updated', 200);
            }

            $cart = Cart::create($cartData);

            return ApiResponse::success($cart, 'Cart item added', 201);
        });
    }

    /**
     * Update an existing cart item (quantity, notes).
     */
    public function update(UpdateCartRequest $request, $id)
    {
        $cart = $this->findCartItem($id);

        $cart->update($request->validated());

        return ApiResponse::success($cart, 'Cart item updated', 200);
    }

    /**
     * Soft delete (remove) an item from the cart.
     */
    public function destroy($id)
    {
        $cart = $this->findCartItem($id);

        if (!$cart) {
            return ApiResponse::error('cart item not found', 404);
        }
        
        $cart->delete();

        return ApiResponse::success(message:'Cart item deleted.');
    }

    /**
     * 
     * Restore a previously deleted cart item.
     */
    public function restore($id)
    {
        $cart = Cart::withTrashed()->find($id);

        if (!$cart) {
            return ApiResponse::error('cart item not found', 404);
        }

        if ($this->ownsCart($cart)) {
            $cart->restore();
            return ApiResponse::success($cart, 'Cart item restored.');
        }

        abort(403, 'Unauthorized.');
    }

    /**
     * Helper to find cart item (only own items).
     */
    protected function findCartItem($id)
    {
        $query = Cart::query();

        if (Auth::check()) {
            $query->where('user_id', Auth::user()->id);
        } else {
            $query->where('session_id', session('session_id'));
        }

        return $query->find($id);
    }

    /**
     * Helper to check if user owns the cart item.
     */
    protected function ownsCart(Cart $cart)
    {
        if (Auth::check()) {
            return $cart->user_id === Auth::user()->id;
        }

        return $cart->session_id === session('session_id');
    }
}