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
use App\Services\Products\WishlistService;
use App\Http\Requests\StoreWishlistRequest;
use App\Services\Products\ProductVariantService;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class WishlistController extends Controller
{
    /**
     * Constructor to inject the WishlistService dependency.
     *
     * @param WishlistService $wishlistService The service responsible for wishlist-related operations.
     */
    public function __construct(protected WishlistService $wishlistService, protected ProductVariantService $productVariantService) {}

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
                return ApiResponse::error('Unauthorized access to wishlist.', 401);
            }

            $wishlists = $paginate
                ? $this->wishlistService->getAllWishlists(
                    perPage: $perPage,
                    columns: $columns,
                    pageName: $pageName,
                    page: $page,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                )
                : $this->wishlistService->getAllWishlists(
                    columns: $columns,
                    withTrashed: $withTrashed,
                    onlyTrashed: $onlyTrashed,
                    conditions: $conditions
                );

            return ApiResponse::success($wishlists, 'Wishlists retrieved successfully.');
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function show(ValidateColumnAndConditionRequest $request, string $id): JsonResponse
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];

            $wishlist = $this->wishlistService->getWishlistById($id, $columns);

            if (!$wishlist) {
                return ApiResponse::error('Wishlist not found.', 404);
            }

            // Ownership check (either user or guest via session)
            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            if (
                ($userId && $wishlist->user_id !== $userId) ||
                ($sessionId && $wishlist->session_id !== $sessionId)
            ) {
                return ApiResponse::error('Unauthorized access to this wishlist.', 403);
            }

            return ApiResponse::success($wishlist, 'Wishlist retrieved successfully.');
        } catch (Exception $e) {
            Log::error("Error retrieving wishlist: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function store(StoreWishlistRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // Ensure we associate wishlist either with user or guest session (not both or neither)
            if (!$userId && !$sessionId) {
                return ApiResponse::error('User or session must be available to create a wishlist item.', 400);
            }

            // Fetch the price, ensuring the variant exists
            $variantQuery = DB::table('product_variants')
                ->where('product_id', $validated['product_id'])
                ->where('id', $validated['variant_id'] ?? -1); // Ensure variant exists
            $variant = $variantQuery->first();

            if (!$variant) {
                return ApiResponse::error('The selected product variant does not exist or is invalid.', 404);
            }

            // Prepare the wishlist data
            $wishlistData = [
                'user_id'    => $userId,
                'session_id' => $userId ? null : $sessionId, // session_id used only for guests
                'product_id' => $validated['product_id'],
                'variant_id' => $validated['variant_id'],
                'wishlist_name' => $validated['wishlist_name'],
                'notes'      => $validated['notes'] ?? null,
                'expires_at' => now()->addDays(30), // or make this dynamic
            ];

            // Begin transaction to ensure atomicity
            DB::beginTransaction();

            // Use a service or model method to upsert based on (user/session + product + variant)
            $wishlist = $this->wishlistService->create($wishlistData);

            // Commit transaction
            DB::commit();

            return ApiResponse::success($wishlist, 'Wishlist created successfully.');
        } catch (Exception $e) {
            // Rollback transaction in case of any error
            DB::rollBack();

            // Log the exception for debugging
            Log::error("Error creating wishlist: {$e->getMessage()}", ['exception' => $e]);

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
                'wishlist_name'    => 'sometimes|string|max:500',
                'notes'       => 'sometimes|string',
                'notify_preferences' => 'sometimes|in:none,discount,restock,both', // Enum-like field for notification preferences
            ]);

            // Find the existing wishlist item
            $wishlistItem = $this->wishlistService->getWishlistById($id);

            if (!$wishlistItem) {
                return ApiResponse::error('Wishlist item not found.', 404);
            }

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // 🛡 Ensure the user or session owns this wishlist item
            if (
                ($wishlistItem->user_id && $wishlistItem->user_id !== $userId) ||
                ($wishlistItem->session_id && $wishlistItem->session_id !== $sessionId)
            ) {
                return ApiResponse::error('You are not authorized to update this wishlist item.', 403);
            }

            // Optional variant lookup
            $variant = null;
            if (isset($validated['variant_id']) || isset($validated['product_id'])) {
                $variant = DB::table('product_variants')
                    ->where('product_id', $validated['product_id'] ?? $wishlistItem->product_id)
                    ->where('id', $validated['variant_id'] ?? $wishlistItem->variant_id)
                    ->first();

                if (!$variant) {
                    return ApiResponse::error('The selected product variant does not exist or is invalid.', 404);
                }
            }


            // Merge fallback values
            $wishlistData = [
                'product_id'                => $validated['product_id'] ?? $wishlistItem->product_id,
                'variant_id'                => $validated['variant_id'] ?? $wishlistItem->variant_id,
                'wishlist_name'             => $validated['wishlist_name'] ?? $wishlistItem->wishlist_name,
                'notes'                     => $validated['notes'] ?? $wishlistItem->notes,
                'notify_preferences'        => $validated['notify_preferences'] ?? $wishlistItem->notify_preferences,
                'expires_at'                => now()->addDays(30),
            ];

            // Begin transaction to ensure atomicity
            DB::beginTransaction();

            // Update the wishlist item
            $wishlistItem->update($wishlistData);

            // Commit transaction
            DB::commit();

            return ApiResponse::success($wishlistItem, 'Wishlist item updated successfully.');
        } catch (Exception $e) {
            // Rollback transaction in case of any error
            DB::rollBack();

            // Log the exception for debugging
            Log::error("Error updating wishlist: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }


    public function delete(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $forceDelete = $request->validated()['force'] ?? false;

            // Get wishlist item
            $wishlistItem = $this->wishlistService->getWishlistById($id);

            if (!$wishlistItem) {
                return ApiResponse::error('Wishlist item not found.', 404);
            }

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // 🛡 Ensure the user or session owns this wishlist item
            if (
                ($wishlistItem->user_id && $wishlistItem->user_id !== $userId) ||
                ($wishlistItem->session_id && $wishlistItem->session_id !== $sessionId)
            ) {
                return ApiResponse::error('You are not authorized to delete this wishlist item.', 403);
            }

            $deleted = $this->wishlistService->delete($id, $forceDelete);

            // Optionally log audit
            Log::info('Wishlist item deleted', [
                'wishlist_id'    => $id,
                'force'      => $forceDelete,
                'user_id'    => $userId,
                'session_id' => $sessionId,
            ]);

            return ApiResponse::success([
                'deleted' => true,
                'type' => $forceDelete ? 'force' : 'soft',
            ], $forceDelete ? 'Wishlist permanently deleted successfully.' : 'Wishlist soft deleted successfully.');
        } catch (Exception $e) {
            Log::error("Error deleting wishlist: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function isSoftDeleted(string $id)
    {
        try {
            $isDeleted = $this->wishlistService->softDeleted($id);

            if ($isDeleted === null) {
                return ApiResponse::error('Wishlist not found.', 404);
            }

            return ApiResponse::success([
                'soft_deleted' => (bool) $isDeleted,
            ], $isDeleted ? 'Wishlist is soft deleted' : 'Wishlist is not soft deleted');
        } catch (Exception $e) {
            Log::error("Error checking soft deleted wishlist: {$e->getMessage()}", ['exception' => $e]);

            return ApiResponse::error($e->getMessage(), 500);
        }
    }

    public function restore(ValidateColumnAndConditionRequest $request, string $id)
    {
        try {
            $columns = $request->validated()['columns'] ?? ['*'];


            // Get wishlist item
            $wishlistItem = $this->wishlistService->getAllWishlists(conditions: ["id:=:" . $id], withTrashed: true)->first();

            if (!$wishlistItem) {
                return ApiResponse::error('Wishlist item not found.', 404);
            }

            $userId = $request->user()?->id;
            $sessionId = $request->hasSession() ? $request->session()?->getId() : null;

            // 🛡 Ensure the user or session owns this wishlist item
            if (
                ($wishlistItem->user_id && $wishlistItem->user_id !== $userId) ||
                ($wishlistItem->session_id && $wishlistItem->session_id !== $sessionId)
            ) {
                return ApiResponse::error('You are not authorized to restore this wishlist item.', 403);
            }


            $wishlist = $this->wishlistService->restore($id, $columns);

            return ApiResponse::success($wishlist, 'Wishlist is restored');
        } catch (Exception $e) {
            // Log the exception for debugging.
            Log::error("Error restoring soft deleted wishlist: {$e->getMessage()}", ['exception' => $e]);

            // Return an error response.
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}