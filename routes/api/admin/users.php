<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\RoleController;

use App\Http\Controllers\Api\Admin\UserController;

use App\Http\Controllers\Api\Admin\PhoneController;

use App\Http\Controllers\Api\Public\CartController;

use App\Http\Controllers\Api\Admin\StatusController;


use App\Http\Controllers\Api\Admin\VendorController;
use App\Http\Controllers\Api\Public\WishlistController;

use App\Http\Controllers\Api\Admin\PermissionController;

use App\Http\Controllers\Api\Public\Orders\OrderController;
use App\Http\Controllers\Api\Admin\RolePermissionController;

use App\Http\Controllers\Api\Vendor\Warehouses\WarehouseController;
use App\Http\Controllers\Api\Admin\Shipping\ShippingCarrierController as AdminShippingCarrierController;
use App\Http\Controllers\Api\vendor\Shipping\ShippingCarrierController as VendorShippingCarrierController;
use App\Http\Controllers\Api\Public\Shipping\ShippingAddressController;

use App\Http\Controllers\Api\Vendor\Warehouses\WarehouseZoneController;
use App\Http\Controllers\Api\Admin\Products\CategoryHierarchyController;
use App\Http\Controllers\Api\Vendor\Products\BrandAndCategoryController;

use App\Http\Controllers\Api\Vendor\Products\ProductInventoryController;

use App\Http\Controllers\Api\Admin\Address\CityController as AdminCityController;
use App\Http\Controllers\Api\Public\Address\CityController as PublicCityController;

use App\Http\Controllers\Api\Admin\Products\BrandController as AdminBrandController;
use App\Http\Controllers\Api\Public\Products\BrandController as PublicBrandController;

use App\Http\Controllers\Api\Admin\Address\CountryController as AdminCountryController;
use App\Http\Controllers\Api\Admin\Orders\CurrencyController as AdminCurrencyController;
use App\Http\Controllers\Api\Admin\Products\ProductController as AdminProductController;
use App\Http\Controllers\Api\Public\Address\CountryController as PublicCountryController;
use App\Http\Controllers\Api\Admin\Products\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Public\Orders\CurrencyController as PublicCurrencyController;
use App\Http\Controllers\Api\Public\Products\ProductController as PublicProductController;
use App\Http\Controllers\Api\Vendor\Products\ProductController as VendorProductController;
use App\Http\Controllers\Api\Admin\Address\ContinentController as AdminContinentController;
use App\Http\Controllers\Api\Public\Products\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\Public\Address\ContinentController as PublicContinentController;
use App\Http\Controllers\Api\Vendor\ProductsWarehousesManagement\InventoryLocationController;
use App\Http\Controllers\Api\Public\Products\ProductMediaController as PublicProductMediaController;
use App\Http\Controllers\Api\Vendor\Products\ProductMediaController as VendorProductMediaController;
use App\Http\Controllers\Api\Admin\Products\ProductStatusController  as AdminProductStatusController;
use App\Http\Controllers\Api\Public\Products\ProductReviewController as PublicProductReviewController;
use App\Http\Controllers\Api\Public\Products\ProductStatusController as PublicProductStatusController;
use App\Http\Controllers\Api\Vendor\Products\ProductReviewController as VendorProductReviewController;
use App\Http\Controllers\Api\Vendor\ProductsWarehousesManagement\ManageProductsInWarehousesController;
use App\Http\Controllers\Api\Public\Products\ProductDiscountController as PublicProductDiscountController;
use App\Http\Controllers\Api\Vendor\Products\ProductDiscountController as VendorProductDiscountController;
use App\Http\Controllers\Api\Public\Products\ProductvariationController as PublicProductvariationController;
use App\Http\Controllers\Api\Vendor\Products\ProductvariationController as VendorProductvariationController;

Route::prefix('users')
    // ->middleware('auth:sanctum,admin')
    ->controller(UserController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('{id}', 'show');
        Route::get('search/where', action: 'searchBy');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::put('update/bulk', 'updateBulk');
        Route::delete('{id}', 'delete');
        Route::delete('delete/bulk', 'deleteBulk');
        Route::get('delete/check/{id}', 'isSoftDeleted');
        Route::post('{id}', 'restore');
        Route::post('restore/bulk/users', 'restoreBulk');
    });


Route::group(['prefix' => 'statuses'], function () {
    Route::get('/', [StatusController::class, 'index']);
    Route::post('/', [StatusController::class, 'store']);
    Route::get('{id}', [StatusController::class, 'show']);
    Route::put('{id}', [StatusController::class, 'update']);
    Route::delete('{id}', [StatusController::class, 'delete']);
    Route::get('deleted/{id}', [StatusController::class, 'isSoftDeleted']);
    Route::post('{id}', [StatusController::class, 'restore']);
});


Route::group(['prefix' => 'roles'], function () {
    Route::get('/', [RoleController::class, 'index']);
    Route::post('/', [RoleController::class, 'store']);
    Route::get('{id}', [RoleController::class, 'show']);
    Route::put('{id}', [RoleController::class, 'update']);
    Route::delete('{id}', [RoleController::class, 'delete']);
    Route::get('deleted/{id}', [RoleController::class, 'isSoftDeleted']);
    Route::post('{id}', [RoleController::class, 'restore']);

    Route::post('{role_id}/permissions/{permission_id}/id', [RolePermissionController::class, 'assignPermissionByID']);
    Route::post('{role_name}/permissions/{permission_name}/name', [RolePermissionController::class, 'assignPermissionByName']);
    Route::delete('{role_id}/permissions/{permission_id}/id', [RolePermissionController::class, 'removePermissionByID']);
    Route::delete('{role_name}/permissions/{permission_name}/name', [RolePermissionController::class, 'removePermissionByName']);

    Route::post('{role_name}/user/{id}', [RolePermissionController::class, 'assignRoleToUser']);
});


Route::group(['prefix' => 'permissions'], function () {
    Route::get('/', [PermissionController::class, 'index']);
    Route::post('/', [PermissionController::class, 'store']);
    Route::get('{id}', [PermissionController::class, 'show']);
    Route::put('{id}', [PermissionController::class, 'update']);
    Route::delete('{id}', [PermissionController::class, 'delete']);
    Route::get('deleted/{id}', [PermissionController::class, 'isSoftDeleted']);
    Route::post('{id}', [PermissionController::class, 'restore']);
});


Route::prefix('phones')
    // ->middleware('auth:sanctum,admin')
    ->controller(PhoneController::class)
    ->group(function () {
        Route::get('/', 'index');
        Route::get('{id}', 'show');
        Route::get('search/where', action: 'searchBy');
        Route::get('user/{id}', action: 'userPhones');
        Route::post('/', 'store');
        Route::put('{id}', 'update');
        Route::put('update/bulk', 'updateBulk');
        Route::delete('{id}', 'delete');
        Route::delete('delete/bulk', 'deleteBulk');
        Route::get('delete/check/{id}', 'isSoftDeleted');
        Route::post('{id}', 'restore');
        Route::post('restore/bulk/phones', 'restoreBulk');
    });




#--------------------------------------------------------------------------------------------------------
Route::prefix('continents')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicContinentController::class, 'index']);
        Route::get('{id}', [PublicContinentController::class, 'show']);
        Route::get('search/{name}', [PublicContinentController::class, 'search']);

        // Admin routes (protected by admin middleware)
        // Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/', [AdminContinentController::class, 'store']);
        Route::put('{id}', [AdminContinentController::class, 'update']);
        Route::delete('{id}', [AdminContinentController::class, 'delete']);
        Route::get('delete/check/{id}', [AdminContinentController::class, 'isSoftDeleted']);
        Route::post('{id}', [AdminContinentController::class, 'restore']);
        // });
    });
#--------------------------------------------------------------------------------------------------------
Route::prefix('countries')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicCountryController::class, 'index']);
        Route::get('{id}', [PublicCountryController::class, 'show']);
        Route::get('search/{name}', [PublicCountryController::class, 'search']);

        // Admin routes (protected by admin middleware)
        // Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/', [AdminCountryController::class, 'store']);
        Route::put('{id}', [AdminCountryController::class, 'update']);
        Route::delete('{id}', [AdminCountryController::class, 'delete']);
        Route::get('delete/check/{id}', [AdminCountryController::class, 'isSoftDeleted']);
        Route::post('{id}', [AdminCountryController::class, 'restore']);
        // });
    });
#--------------------------------------------------------------------------------------------------------
Route::prefix('cities')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicCityController::class, 'index']);
        Route::get('{id}', [PublicCityController::class, 'show']);
        Route::get('search/{name}', [PublicCityController::class, 'search']);

        // Admin routes (protected by admin middleware)
        // Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
        Route::post('/', [AdminCityController::class, 'store']);
        Route::put('{id}', [AdminCityController::class, 'update']);
        Route::delete('{id}', [AdminCityController::class, 'delete']);
        Route::get('delete/check/{id}', [AdminCityController::class, 'isSoftDeleted']);
        Route::post('{id}', [AdminCityController::class, 'restore']);
        // });
    });
#--------------------------------------------------------------------------------------------------------
Route::middleware(['auth:sanctum'])
    ->prefix('admin/currencies')
    ->group(function () {
        Route::post('/', [AdminCurrencyController::class, 'store']);
        Route::patch('{code}', [AdminCurrencyController::class, 'update']);
        Route::delete('{code}', [AdminCurrencyController::class, 'delete']);
    });

Route::middleware(['auth:sanctum'])
    ->prefix('public/currencies')
    ->group(function () {
        Route::get('/', [PublicCurrencyController::class, 'index']);
        Route::get('{code}', [PublicCurrencyController::class, 'show']);
        Route::get('search/{query}', [PublicCurrencyController::class, 'search']);
    });
#--------------------------------------------------------------------------------------------------------




Route::middleware('auth:sanctum')
    ->prefix('admin/vendors')
    ->group(function () {
        Route::get('/', [VendorController::class, 'index']);
        Route::get('{id}', [VendorController::class, 'show']);
        Route::get('search/where', [VendorController::class, 'searchBy']);
        Route::post('/', [VendorController::class, 'store']);
        Route::put('{id}', [VendorController::class, 'update']);
        Route::put('update/bulk', [VendorController::class, 'updateBulk']);
        Route::delete('{id}', [VendorController::class, 'delete']);
        Route::delete('delete/bulk', [VendorController::class, 'deleteBulk']);
        Route::get('delete/check/{id}', [VendorController::class, 'isSoftDeleted']);
        Route::post('{id}', [VendorController::class, 'restore']);
        Route::post('restore/bulk/vendors', [VendorController::class, 'restoreBulk']);
    });

// Route::group(["prefix" => "vendors"], function () {
//     Route::get('{id}', [VendorController::class, 'show']);
//     Route::post('/', [VendorController::class, 'store']);
//     Route::get('/check-me/{id}', [VendorController::class, 'isApproved']);
//     Route::put('{id}', [VendorController::class, 'update']);
// });


Route::prefix('vendor')->middleware(['auth:sanctum'])->group(function () {
    // Warehouses
    Route::get('warehouses', [WarehouseController::class, 'index']);
    Route::get('warehouses/{warehouse}', [WarehouseController::class, 'show']);
    Route::get('warehouses/search/{query}', [WarehouseController::class, 'search']);
    Route::get('warehouses/location/{warehouse}', [WarehouseController::class, 'location']);
    Route::post('warehouses', [WarehouseController::class, 'store']);
    Route::put('warehouses/{warehouse}', [WarehouseController::class, 'update']);
    Route::patch('warehouses/{warehouse}/status', [WarehouseController::class, 'updateStatus']);
    Route::delete('warehouses/{warehouse}', [WarehouseController::class, 'delete']);

    // Warehouse Zones
    Route::get('warehouses/{warehouse}/zones', [WarehouseZoneController::class, 'index']);
    Route::get('zones/{id}', [WarehouseZoneController::class, 'show']);
    Route::get('warehouses/zones/location/{zone}', [WarehouseZoneController::class, 'location']);
    Route::get('warehouses/{warehouse}/zones/search/{query}', [WarehouseZoneController::class, 'search']);
    Route::post('warehouses/{warehouse}/zones', [WarehouseZoneController::class, 'store']);
    Route::put('zones/{id}', [WarehouseZoneController::class, 'update']);
    Route::delete('zones/{id}', [WarehouseZoneController::class, 'delete']);
});


#--------------------------------------------------------------------------------------------------------
Route::prefix('public/brands')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicBrandController::class, 'index']);
        Route::get('{id}', [PublicBrandController::class, 'show']);
        Route::get('search/{query}', [PublicBrandController::class, 'search']);
    });

Route:: //middleware(['auth:sanctum', 'role:admin'])
    prefix('admin/brands')
    ->group(function () {
        Route::post('/', [AdminBrandController::class, 'store']);
        Route::post('{id}', [AdminBrandController::class, 'update']);
        Route::delete('{id}', [AdminBrandController::class, 'delete']);
        Route::get('delete/check/{id}', [AdminBrandController::class, 'isSoftDeleted']);
        Route::post('/restore/{id}', [AdminBrandController::class, 'restore']);
    });

Route:: //middleware(['auth:sanctum', 'role:vendor'])
    prefix('vendor/brands')
    ->group(function () {
        Route::post('/register', [BrandAndCategoryController::class, 'registerBrand']);
    });
#--------------------------------------------------------------------------------------------------------
#--------------------------------------------------------------------------------------------------------
Route::prefix('public/categories')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicCategoryController::class, 'index']);
        Route::get('{id}', [PublicCategoryController::class, 'show']);
        Route::get('search/{query}', [PublicCategoryController::class, 'search']);
    });

Route:: //middleware(['auth:sanctum', 'role:admin'])
    prefix('admin/categories')
    ->group(function () {
        Route::post('/{parent?}', [AdminCategoryController::class, 'store']);
        Route::put('{id}', [AdminCategoryController::class, 'update']);
        Route::delete('{id}', [AdminCategoryController::class, 'delete']);
        Route::get('delete/check/{id}', [AdminCategoryController::class, 'isSoftDeleted']);
        Route::post('/restore/{id}', [AdminCategoryController::class, 'restore']);
    });

Route:: //middleware(['auth:sanctum', 'role:vendor'])
    prefix('vendor/categories')
    ->group(function () {
        Route::post('/register/{parent?}', [BrandAndCategoryController::class, 'registerCategory']);
    });

Route::prefix('admin/category-hierarchy')->group(function () {
    Route::get('/', [CategoryHierarchyController::class, 'index']);         // List all hierarchy relationships
    Route::post('/', [CategoryHierarchyController::class, 'store']);        // Create a new parent-child relationship
    Route::delete('{id}', [CategoryHierarchyController::class, 'destroy']); // Delete a relationship by ID
});
#--------------------------------------------------------------------------------------------------------

#--------------------------------------------------------------------------------------------------------

Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:vendor'])
    ->prefix('public/products')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicProductController::class, 'index']);
        Route::get('{product}', [PublicProductController::class, 'show']);
        Route::get('search/{query}', [PublicProductController::class, 'search']);

        Route::get('/variations/all', [PublicProductvariationController::class, 'index']);
        Route::get('/variations/{variant}', [PublicProductvariationController::class, 'show']);
        Route::get('/variations/search/{query}', [PublicProductvariationController::class, 'search']);
        Route::get('{product}/variations', [PublicProductvariationController::class, 'variationsOfSpecificProduct']);
        Route::get('{product}/variations/{variant}', [PublicProductvariationController::class, 'variationOfSpecificProduct']);

        Route::get('/media/all', [PublicProductMediaController::class, 'index']);
        Route::get('/media/{id}', [PublicProductMediaController::class, 'show']);
        Route::get('/media/search/{query}', [PublicProductMediaController::class, 'search']);
        Route::get('{product}/media', [PublicProductMediaController::class, 'mediaOfSpecificProduct']);
        Route::get('{product}/media/{media}', [PublicProductMediaController::class, 'specificMediaOfSpecificProduct']);

        Route::get('/discounts/all', [PublicProductDiscountController::class, 'index']);
        Route::get('/discounts/{discount}', [PublicProductDiscountController::class, 'show']);
        Route::get('/discounts/search/{query}', [PublicProductDiscountController::class, 'search']);
        Route::get('{product}/discounts', [PublicProductDiscountController::class, 'discountOfSpecificProduct']);
        Route::get('{product}/discounts/{discount}', [PublicProductDiscountController::class, 'specificDiscountOfSpecificProduct']);

        Route::get('/reviews/all', [PublicProductReviewController::class, 'index']);
        Route::get('/reviews/{review}', [PublicProductReviewController::class, 'show']);
        Route::get('{product}/reviews', [PublicProductReviewController::class, 'ReviewsOfSpecificProduct']);
        Route::get('{product}/reviews/{review}', [PublicProductReviewController::class, 'ReviewOfSpecificProduct']);
        #----------
        Route::post('reviews', [PublicProductReviewController::class, 'store']);
        Route::patch('reviews/{id}', [PublicProductReviewController::class, 'update']);
        Route::delete('reviews/{id}', [VendorProductReviewController::class, 'delete']);
    });


Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:vendor'])
    ->prefix('vendor/products')
    ->group(function () {
        Route::post('/', [VendorProductController::class, 'store']);
        Route::put('{id}', [VendorProductController::class, 'update']);
        Route::delete('{id}', [VendorProductController::class, 'delete']);
        Route::get('delete/check/{id}', [VendorProductController::class, 'isSoftDeleted']);
        Route::post('restore/{id}', [VendorProductController::class, 'restore']);

        Route::post('variations', [VendorProductvariationController::class, 'store']);
        Route::patch('variations/{id}', [VendorProductvariationController::class, 'update']);
        Route::delete('variations/{id}', [VendorProductvariationController::class, 'delete']);
        Route::get('variations/delete-check/{id}', [VendorProductvariationController::class, 'isSoftDeleted']);
        Route::post('variations/restore/{id}', [VendorProductvariationController::class, 'restore']);

        Route::post('media', [VendorProductMediaController::class, 'store']);
        Route::post('media/{id}', [VendorProductMediaController::class, 'update']);
        Route::delete('media/{id}', [VendorProductMediaController::class, 'delete']);
        Route::delete('{product}/media', [VendorProductMediaController::class, 'deleteAllProductMedias']);

        Route::post('discounts', [VendorProductDiscountController::class, 'store']);
        Route::patch('discounts/{id}', [VendorProductDiscountController::class, 'update']);
        Route::delete('discounts/{id}', [VendorProductDiscountController::class, 'delete']);
        Route::delete('{productOrVariation}/discounts', [VendorProductDiscountController::class, 'deleteAllProductDiscounts']);
        Route::get('discounts/delete-check/{id}', [VendorProductDiscountController::class, 'isSoftDeleted']);
        Route::post('discounts/restore/{id}', [VendorProductDiscountController::class, 'restore']);

        Route::patch('reviews/{id}', [VendorProductReviewController::class, 'update']);
        Route::patch('{product}/reviews/update-bulk', [VendorProductReviewController::class, 'updateBulk']);
        Route::delete('reviews/{id}', [VendorProductReviewController::class, 'delete']);
        Route::delete('{product}/reviews/delete-bulk', [VendorProductReviewController::class, 'deleteAllProductReviews']);
        Route::get('reviews/delete-check/{id}', [VendorProductReviewController::class, 'isSoftDeleted']);
        Route::post('reviews/restore/{id}', [VendorProductReviewController::class, 'restore']);
        Route::post('{product}/reviews/restore-bulk', [VendorProductReviewController::class, 'restoreAllProductReviews']);
    });

Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:vendor'])
    ->prefix('vendor/products-in-warehouse')
    ->group(function () {
        Route::post('/stock-in', [ManageProductsInWarehousesController::class, 'stockIn']);
        Route::post('/stock-out', [ManageProductsInWarehousesController::class, 'stockOut']);
        Route::patch('/update/{inventory}', [ManageProductsInWarehousesController::class, 'updateSettings']);
        Route::get('/low-stock', [ManageProductsInWarehousesController::class, 'lowStock']);
        Route::get('{warehouse}', [ManageProductsInWarehousesController::class, 'index']);
        Route::get('{warehouse}/products', [ManageProductsInWarehousesController::class, 'allProductsInWarehouses']);
        Route::get('{warehouse}/products/{product}', [ManageProductsInWarehousesController::class, 'specificProductInWarehouse']);
        Route::get('{warehouse}/products/{product}/variations/{variation}', [ManageProductsInWarehousesController::class, 'specificVariationInWarehouse']);
    });











// Route:: //middleware(['auth:sanctum', 'role:admin'])
//     prefix('admin/products')
//     ->group(function () {
//         Route::get('/pendding', [AdminProductController::class, 'pendding']);
//         Route::patch('/approve/{product}', [AdminProductController::class, 'approve']);
//         Route::patch('/reject/{product}', [AdminProductController::class, 'reject']);
//         Route::patch('/change-vendor/{old_vendor}/{new_vendor}', [AdminProductController::class, 'changeVendor']);
//     });


Route::middleware(['auth:sanctum'])
    ->prefix('public/cart-items')
    ->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::get('{item}', [CartController::class, 'show']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('{item}', [CartController::class, 'update']);
        Route::delete('{item}', [CartController::class, 'destroy']);
        Route::post('{item}/restore', [CartController::class, 'restore']);
    });


Route::middleware(['auth:sanctum'])
    ->prefix('public/wishlist-items')
    ->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::get('{item}', [WishlistController::class, 'show']);
        Route::get('/name/{name}', [WishlistController::class, 'getByWishlistName']);

        Route::post('/', [WishlistController::class, 'store']);
        Route::put('{item}', [WishlistController::class, 'update']);
        Route::delete('{item}', [WishlistController::class, 'delete']);
        Route::post('{item}/restore', [WishlistController::class, 'restore']);

        Route::post('/migrate', [WishlistController::class, 'migrateSessionToUser']);
    });
#--------------------------------------------------------------------------------------------------------

Route::middleware(['auth:sanctum'])
    ->prefix('public/orders')
    ->group(function () {

        Route::get('checkout', [OrderController::class, 'getCheckout']);
        Route::get('/', [OrderController::class, 'index']);
        // Route::get('{orderId}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
        // Route::patch('{orderId}', [OrderController::class, 'update']);

        // Route::get('statuses/all', [OrderController::class, 'ordersStatuses']);
        // Route::get('{orderId}/statuses', [OrderController::class, 'updateOrderStatus']);


        // Route::get('all/items', [OrderItemController::class, 'index']);
        // Route::get('{orderId}/items', [OrderItemController::class, 'show']);
        // Route::get('{orderId}/items/{itemId}', [OrderItemController::class, 'show']);

        // Route::post('{orderId}/items', [OrderItemController::class, 'store']);
        // Route::patch('{orderId}/items/{itemId}', [OrderItemController::class, 'update']);
        // Route::delete('{orderId}/items/{itemId}', [OrderItemController::class, 'delete']);
    });

Route::middleware(['auth:sanctum'])
    ->prefix('public/shipping')
    ->group(function () {
        Route::get('addresses', [ShippingAddressController::class, 'index']);
        Route::get('addresses/{address}', [ShippingAddressController::class, 'show']);
        Route::post('addresses', [ShippingAddressController::class, 'store']);
        Route::put('addresses/{address}', [ShippingAddressController::class, 'update']);
        Route::delete('addresses/{address}', [ShippingAddressController::class, 'destroy']);
        Route::post('addresses/{address}/restore', [ShippingAddressController::class, 'restore']);
    });

Route::middleware(['auth:sanctum']) // role : admin
    ->prefix('admin/shipping')
    ->group(function () {
        Route::get('carriers', [AdminShippingCarrierController::class, 'index']);
        Route::get('carriers/{carrier}', [AdminShippingCarrierController::class, 'show']);
        Route::post('carriers', [AdminShippingCarrierController::class, 'store']);
        Route::put('carriers/{carrier}', [AdminShippingCarrierController::class, 'update']);
        Route::delete('carriers/{carrier}', [AdminShippingCarrierController::class, 'destroy']);
        Route::post('carriers/{carrier}/restore', [AdminShippingCarrierController::class, 'restore']);
    });

Route::middleware(['auth:sanctum']) // role : vendor
    ->prefix('vendor/shipping')
    ->group(function () {
        Route::get('carriers', [VendorShippingCarrierController::class, 'index']);
        Route::get('carriers/{carrier}', [VendorShippingCarrierController::class, 'show']);
        Route::post('carriers', [VendorShippingCarrierController::class, 'store']);
        Route::put('carriers/{carrier}', [VendorShippingCarrierController::class, 'update']);
        Route::delete('carriers/{carrier}', [VendorShippingCarrierController::class, 'destroy']);
        Route::post('carriers/{carrier}/restore', [VendorShippingCarrierController::class, 'restore']);
    });

// Route::middleware(['auth:sanctum'])
// ->prefix('public/shipping')
// ->group(function () {

//     Route::get('addresses', [ShippingAddressController::class, 'index']);
//     Route::get('addresses/{address}', [ShippingAddressController::class, 'show']);
//     Route::post('addresses', [ShippingAddressController::class, 'store']);
//     Route::put('addresses/{address}', [ShippingAddressController::class, 'update']);
//     Route::delete('addresses/{address}', [ShippingAddressController::class, 'destroy']);
//     Route::post('addresses/{address}/restore', [ShippingAddressController::class, 'restore']);
// });

// Route::middleware(['auth:sanctum'])
// ->prefix('public/shipping')
// ->group(function () {

//     Route::get('addresses', [ShippingAddressController::class, 'index']);
//     Route::get('addresses/{address}', [ShippingAddressController::class, 'show']);
//     Route::post('addresses', [ShippingAddressController::class, 'store']);
//     Route::put('addresses/{address}', [ShippingAddressController::class, 'update']);
//     Route::delete('addresses/{address}', [ShippingAddressController::class, 'destroy']);
//     Route::post('addresses/{address}/restore', [ShippingAddressController::class, 'restore']);
// });

#--------------------------------------------------------------------------------------------------------


Route::get('test', function (Request $request) {

    $user = Auth::user();

    $cartItems = $user->cartItems;
    // with(['product', 'variation'])->get()
    // $ids = [];
    // foreach ($cartItems as $cartItem) {
    //     $ids[] = $cartItem->id; 
    // }

    $subTotal = $cartItems->sum(function ($item) {
        return $item->price * $item->quantity;
    });
    return $cartItems;
    // return $subTotal;

})->middleware('auth:sanctum');