<?php

namespace App\Http\Controllers\Api\Public;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaginateRequest;
use App\Services\Products\CartService;
use App\Http\Requests\StoreCartRequest;
use App\Services\Products\ProductVariantService;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class CartController extends Controller
{
    /**
     * Constructor to inject the CartService dependency.
     *
     * @param CartService $cartService The service responsible for cart-related operations.
     */
    public function __construct(protected CartService $cartService, protected ProductVariantService $productVariantService) {}

    public function index(PaginateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $paginate     = $validated['paginate']     ?? false;
            $withTrashed  = $validated['with_trashed'] ?? false;
            $onlyTrashed  = $validated['only_trashed'] ?? false;
            $conditions   = $validated['conditions']   ?? [];
            $columns      = $validated['columns']      ?? ['*'];
            $perPage      = $validated['per_page']     ?? 15;
            $pageName     = $validated['pageName']     ?? 'page';
            $page         = $validated['page']         ?? 1;

            // Auto-inject user_id or session_id into the condition
            if ($request->user()?->id) {
                $conditions[] = 'user_id:=:' . $request->user()->id;
            } elseif ($request->hasSession()) {
                $conditions[] = 'session_id:=:' . $request->session()->getId();
            } else {
                return ApiResponse::error('Unauthorized access to cart.', 401);
            }

            $carts = $paginate
                ? $this->cartService->getAllCarts(
                    perPage: $perPage,
                    columns: $columns,
                    pageName: $pageName,
                    page: $page,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->cartService->getAllCarts(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($carts, 'Carts retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $cart = $this->cartService->getCartById($id, $columns);

            if (!$cart) {
                return ApiResponse::error('Cart not found.', 404);
            }

            // Ownership check (either user or guest via session)
            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            if (
                ($userId && $cart->user_id !== $userId) ||
                ($sessionId && $cart->session_id !== $sessionId)
            ) {
                return ApiResponse::error('Unauthorized access to this cart.', 403);
            }

            return ApiResponse::success($cart, 'Cart retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving cart: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreCartRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // Ensure we associate cart either with user or guest session (not both or neither)
            if (!$userId && !$sessionId) {
                return ApiResponse::error('User or session must be available to create a cart item.', 400);
            }

            // Fetch the price, ensuring the variant exists
            $variantQuery = DB::table('product_variants')
                ->where('product_id', $validated['product_id'])
                ->where('id', $validated['variant_id'] ?? -1); // Ensure variant exists
            $variant = $variantQuery->first();

            if (!$variant) {
                return ApiResponse::error('The selected product variant does not exist or is invalid.', 404);
            }

            $price = $variant->price;

            if ($variant->stock < $validated['quantity']) {
                return ApiResponse::error("Only {$variant->stock} items available in stock", 422);
            }

            // Prepare the cart data
            $cartData = [
                'user_id'    => $userId,
                'session_id' => $userId ? null : $sessionId, // session_id used only for guests
                'product_id' => $validated['product_id'],
                'variant_id' => $validated['variant_id'],
                'quantity'   => $validated['quantity'] ?? 1,
                'price'      => $price,
                'currency'   => $validated['currency'] ?? 'USD',
                'notes'      => $validated['notes'] ?? null,
                'expires_at' => now()->addDays(30), // or make this dynamic
            ];

            // Begin transaction to ensure atomicity
            DB::beginTransaction();

            // Use a service or model method to upsert based on (user/session + product + variant)
            $cart = $this->cartService->create($cartData);

            // Commit transaction
            DB::commit();

            return ApiResponse::success($cart, 'Cart created successfully.');
        } catch (Exception $e) {
            // Rollback transaction in case of any error
            DB::rollBack();

            // Log the exception for debugging
            Log::error("Error creating cart: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'product_id'  => 'sometimes|exists:products,id',
                'variant_id'  => 'sometimes|exists:product_variants,id',
                'quantity'    => 'sometimes|integer|min:1',
                'currency'    => 'sometimes|string|max:3',
                'notes'       => 'sometimes|string',
            ]);

            // Find the existing cart item
            $cartItem = $this->cartService->getCartById($id);

            if (!$cartItem) {
                return ApiResponse::error('Cart item not found.', 404);
            }

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // 🛡 Ensure the user or session owns this cart item
            if (
                ($cartItem->user_id && $cartItem->user_id !== $userId) ||
                ($cartItem->session_id && $cartItem->session_id !== $sessionId)
            ) {
                return ApiResponse::error('You are not authorized to update this cart item.', 403);
            }

            // Optional variant lookup
            $variant = null;
            if (isset($validated['variant_id']) || isset($validated['product_id'])) {
                $variant = DB::table('product_variants')
                    ->where('product_id', $validated['product_id'] ?? $cartItem->product_id)
                    ->where('id', $validated['variant_id'] ?? $cartItem->variant_id)
                    ->first();

                if (!$variant) {
                    return ApiResponse::error('The selected product variant does not exist or is invalid.', 404);
                }
            }

            // Stock validation
            if ($variant && isset($validated['quantity']) && $variant->stock < $validated['quantity']) {
                return ApiResponse::error('Not enough stock available for the selected variant.', 400);
            }

            // Merge fallback values
            $cartData = [
                'product_id'  => $validated['product_id'] ?? $cartItem->product_id,
                'variant_id'  => $validated['variant_id'] ?? $cartItem->variant_id,
                'quantity'    => $validated['quantity'] ?? $cartItem->quantity,
                'price'       => $variant ? $variant->price : $cartItem->price,
                'currency'    => $validated['currency'] ?? $cartItem->currency,
                'notes'       => $validated['notes'] ?? $cartItem->notes,
                'expires_at'  => now()->addDays(30),
            ];

            // Begin transaction to ensure atomicity
            DB::beginTransaction();

            // Update the cart item
            $cartItem->update($cartData);

            // Commit transaction
            DB::commit();

            return ApiResponse::success($cartItem, 'Cart item updated successfully.');
        } catch (Exception $e) {
            // Rollback transaction in case of any error
            DB::rollBack();

            // Log the exception for debugging
            Log::error("Error updating cart: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }


    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            // Get cart item
            $cartItem = $this->cartService->getCartById($id);

            if (!$cartItem) {
                return ApiResponse::error('Cart item not found.', 404);
            }

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // 🛡 Ensure the user or session owns this cart item
            if (
                ($cartItem->user_id && $cartItem->user_id !== $userId) ||
                ($cartItem->session_id && $cartItem->session_id !== $sessionId)
            ) {
                return ApiResponse::error('You are not authorized to delete this cart item.', 403);
            }

            $deleted = $this->cartService->delete($id, $forceDelete);

            // Optionally log audit
            Log::info('Cart item deleted', [
                'cart_id'    => $id,
                'force'      => $forceDelete,
                'user_id'    => $userId,
                'session_id' => $sessionId,
            ]);

            return ApiResponse::success([
                'deleted' => true,
                'type' => $forceDelete ? 'force' : 'soft',
            ], $forceDelete ? 'Cart permanently deleted successfully.' : 'Cart soft deleted successfully.');
            
        } catch (Exception $e) {
            Log::error("Error deleting cart: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->cartService->softDeleted($id);

            if ($isDeleted === null) {
                return ApiResponse::error('Cart not found.', 404);
            }

            return ApiResponse::success([
                'soft_deleted' => (bool) $isDeleted,
            ], $isDeleted ? 'Cart is soft deleted' : 'Cart is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted cart: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];


            // Get cart item
            $cartItem = $this->cartService->getAllCarts(conditions:["id:=:" . $id], withTrashed:true)->first();

            if (!$cartItem) {
                return ApiResponse::error('Cart item not found.', 404);
            }

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // 🛡 Ensure the user or session owns this cart item
            if (
                ($cartItem->user_id && $cartItem->user_id !== $userId) ||
                ($cartItem->session_id && $cartItem->session_id !== $sessionId)
            ) {
                return ApiResponse::error('You are not authorized to restore this cart item.', 403);
            }

            
            $cart = $this->cartService->restore($id, $columns);

            return ApiResponse::success($cart, 'Cart is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted cart: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}