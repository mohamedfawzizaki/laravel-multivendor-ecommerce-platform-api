<?php

namespace App\Http\Controllers\Api\Public;

use App\Models\Wishlist;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\Products\ProductVariation;
use App\Http\Requests\CartAndWishlist\StoreWishlistRequest;
use App\Http\Requests\CartAndWishlist\UpdateWishlistRequest;

class WishlistController extends Controller
{
    /**
     * Display all wishlist items for the current user or guest session.
     */
    public function index()
    {
        $query = Wishlist::with(['product', 'variation'])
            ->where(function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->where('session_id', session('session_id'));
                }
            })
            ->orderBy('created_at', 'desc');

        $wishlistItems = $query->get();

        return ApiResponse::success($wishlistItems, 'Wishlist items retreived successfully');
    }

    public function show(string $id)
    {
        $wishlistItem = Wishlist::with(['product', 'variation'])
            ->where(function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->where('session_id', session('session_id'));
                }
            })
            ->find($id);

        if (!$wishlistItem) {
            return ApiResponse::error('Wishlist item not found or not accessible', 404);
        }

        return ApiResponse::success($wishlistItem);
    }

    // Get items by wishlist name
    public function getByWishlistName($name)
    {
        $query = Wishlist::with(['product', 'variation'])
            ->where('wishlist_name', $name)
            ->where(function ($query) {
                if (Auth::check()) {
                    $query->where('user_id', Auth::id());
                } else {
                    $query->where('session_id', session('session_id'));
                }
            })
            ->orderBy('created_at', 'desc');

        return ApiResponse::success($query->get(), "Wishlist '{$name}' items retrieved successfully");
    }

    /**
     * Add a new item to the wishlist.
     */
    public function store(StoreWishlistRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $data = $request->validated();
            // $expirationDays = config('wishlist.expiration_days', 180); // 6 months default
            $expirationDays = 180; // 6 months default

            // Verify product exists
            $product = Product::find($data['product_id']);
            if (!$product) {
                return ApiResponse::error('Product not found', 404);
            }

            // Handle variation if provided
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

            $wishlistData = [
                'product_id' => $product->id,
                'variation_id' => $data['variation_id'] ?? null,
                'wishlist_name' => $data['wishlist_name'] ?? 'Default',
                'notes' => $data['notes'] ?? null,
                'notify_preferences' => $data['notify_preferences'] ?? 'none',
                'expires_at' => now()->addDays($expirationDays),
            ];

            // Handle user/session
            if (Auth::check()) {
                $wishlistData['user_id'] = Auth::id();
            } else {
                $sessionId = session('session_id');
                if (!$sessionId) {
                    $sessionId = Str::uuid()->toString();
                    session(['session_id' => $sessionId]);
                }
                $wishlistData['session_id'] = $sessionId;
            }

            // Check if item already exists
            $existing = Wishlist::where(function ($query) use ($wishlistData) {
                if (isset($wishlistData['user_id'])) {
                    $query->where('user_id', $wishlistData['user_id']);
                } else {
                    $query->where('session_id', $wishlistData['session_id']);
                }
            })
                ->where('product_id', $wishlistData['product_id'])
                ->where('variation_id', $wishlistData['variation_id'])
                ->first();

            if ($existing) {
                return ApiResponse::success($existing, 'Item already exists in wishlist', 200);
            }

            $wishlist = Wishlist::create($wishlistData);

            return ApiResponse::success($wishlist->load(['product', 'variation']), 'Item added to wishlist successfully', 201);
        });
    }

    /**
     * Update a wishlist item (like notes, notify_preferences).
     */
    public function update(UpdateWishlistRequest $request, string $wishlistID)
    {
        $wishlistItem = Wishlist::find($wishlistID);

        if (!$wishlistItem) {
            return ApiResponse::error('Wishlist item not found or not accessible', 404);
        }

        $this->authorizeWishlistItem($wishlistItem);

        $data = $request->validated();

        $wishlistItem->update([
            'wishlist_name' => $data['wishlist_name'] ?? $wishlistItem->wishlist_name,
            'notes' => $data['notes'] ?? $wishlistItem->notes,
            'notify_preferences' => $data['notify_preferences'] ?? $wishlistItem->notify_preferences
        ]);

        return ApiResponse::success(
            $wishlistItem->fresh(['product', 'variation']),
            'Wishlist item updated successfully'
        );
    }

    // Remove item from wishlist
    public function delete(string $wishlistID)
    {
        $wishlistItem = Wishlist::find($wishlistID);

        if (!$wishlistItem) {
            return ApiResponse::error('Wishlist item not found or not accessible', 404);
        }
        $this->authorizeWishlistItem($wishlistItem);

        $wishlistItem->delete();

        return ApiResponse::success(message: 'Item removed from wishlist successfully');
    }

    public function restore($id)
    {
        $wishlistItem = Wishlist::withTrashed()->find($id);

        if (!$wishlistItem) {
            return ApiResponse::error('Wishlist item not found', 404);
        }

        $this->authorizeWishlistItem($wishlistItem);

        $wishlistItem->restore();
        
        return ApiResponse::success($wishlistItem, 'Wishlist item restored.');
    }

    // Move items from session to user when they login
    public function migrateSessionToUser()
    {
        if (!Auth::check()) {
            return ApiResponse::error(message: 'Unauthorized', status: 401);
        }

        $sessionId = session('session_id');
        if (!$sessionId) {
            return ApiResponse::error(message: 'No session wishlist items to migrate');
        }

        $migratedCount = Wishlist::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->update([
                'user_id' => Auth::id(),
                'session_id' => null
            ]);

        return ApiResponse::success(message: "Migrated {$migratedCount} items to user account");
    }

    // Protected method to check wishlist item ownership
    protected function authorizeWishlistItem(Wishlist $wishlist)
    {
        if (Auth::check()) {
            if ($wishlist->user_id !== Auth::id()) {
                return ApiResponse::error(message: 'You do not own this wishlist item', status: 403);
            }
        } else {
            if ($wishlist->session_id !== session('session_id')) {
                return ApiResponse::error(message: 'You do not own this wishlist item', status: 403);
            }
        }
    }
}