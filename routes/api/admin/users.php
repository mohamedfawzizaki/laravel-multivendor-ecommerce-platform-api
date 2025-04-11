<?php


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\RoleController;
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\PhoneController;
use App\Http\Controllers\Api\Admin\StatusController;

use App\Http\Controllers\Api\Admin\VendorController;

use App\Http\Controllers\Api\Admin\PermissionController;

use App\Http\Controllers\Api\Vendor\WarehouseController;
use App\Http\Controllers\Api\Admin\RolePermissionController;

use App\Http\Controllers\Api\Public\CartController;
use App\Http\Controllers\Api\Public\WishlistController;

use App\Http\Controllers\Api\Vendor\Products\BrandAndCategoryController;


use App\Http\Controllers\Api\Vendor\Products\ProductInventoryController;
use App\Http\Controllers\Api\Admin\Address\CityController as AdminCityController;

use App\Http\Controllers\Api\Public\Address\CityController as PublicCityController;

use App\Http\Controllers\Api\Admin\Products\BrandController as AdminBrandController;
use App\Http\Controllers\Api\Public\Products\BrandController as PublicBrandController;

use App\Http\Controllers\Api\Admin\Address\CountryController as AdminCountryController;
use App\Http\Controllers\Api\Admin\Products\ProductController as AdminProductController;
use App\Http\Controllers\Api\Public\Address\CountryController as PublicCountryController;

use App\Http\Controllers\Api\Admin\Products\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Public\Products\ProductController as PublicProductController;
use App\Http\Controllers\Api\Vendor\Products\ProductController as VendorProductController;

use App\Http\Controllers\Api\Admin\Address\ContinentController as AdminContinentController;

use App\Http\Controllers\Api\Public\Products\CategoryController as PublicCategoryController;
use App\Http\Controllers\Api\Public\Address\ContinentController as PublicContinentController;

use App\Http\Controllers\Api\Public\Products\ProductImageController as PublicProductImageController;
use App\Http\Controllers\Api\Vendor\Products\ProductImageController as VendorProductImageController;

use App\Http\Controllers\Api\Admin\Products\ProductStatusController  as AdminProductStatusController;
use App\Http\Controllers\Api\Public\Products\ProductReviewController as PublicProductReviewController;
use App\Http\Controllers\Api\Public\Products\ProductStatusController as PublicProductStatusController;
use App\Http\Controllers\Api\Vendor\Products\ProductReviewController as VendorProductReviewController;
use App\Http\Controllers\Api\Public\Products\ProductVariantController as PublicProductVariantController;
use App\Http\Controllers\Api\Vendor\Products\ProductVariantController as VendorProductVariantController;
use App\Http\Controllers\Api\Public\Products\ProductDiscountController as PublicProductDiscountController;
use App\Http\Controllers\Api\Vendor\Products\ProductDiscountController as VendorProductDiscountController;
use App\Models\Currency;

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


#--------------------------------------------------------------------------------------------------------
Route::prefix('public/brands')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicBrandController::class, 'index']);
        Route::get('{id}', [PublicBrandController::class, 'show']);
        Route::get('search/{name}', [PublicBrandController::class, 'search']);
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
        Route::get('search/{name}', [PublicCategoryController::class, 'search']);
    });

Route:: //middleware(['auth:sanctum', 'role:admin'])
    prefix('admin/categories')
    ->group(function () {
        Route::post('/', [AdminCategoryController::class, 'store']);
        Route::put('{id}', [AdminCategoryController::class, 'update']);
        Route::delete('{id}', [AdminCategoryController::class, 'delete']);
        Route::get('delete/check/{id}', [AdminCategoryController::class, 'isSoftDeleted']);
        Route::post('/restore/{id}', [AdminCategoryController::class, 'restore']);
    });

Route:: //middleware(['auth:sanctum', 'role:vendor'])
    prefix('vendor/categories')
    ->group(function () {
        Route::post('/register', [BrandAndCategoryController::class, 'registerCategory']);
    });
#--------------------------------------------------------------------------------------------------------

#--------------------------------------------------------------------------------------------------------

Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:vendor'])
    ->prefix('public/products')
    ->group(function () {
        // Public routes for customers
        Route::get('/', [PublicProductController::class, 'index']);
        Route::get('{id}', [PublicProductController::class, 'show']);
        Route::get('search/{name}', [PublicProductController::class, 'search']);


        Route::get('/statuses/all', [PublicProductStatusController::class, 'index']);
        Route::get('/statuses/show/{id}', [PublicProductStatusController::class, 'show']);
        Route::get('/statuses/search/{name}', [PublicProductStatusController::class, 'search']);

        Route::get('/images/all', [PublicProductImageController::class, 'index']);
        Route::get('/images/{id}', [PublicProductImageController::class, 'show']);
        Route::get('{product}/images', [PublicProductImageController::class, 'ImagesOfSpecificProduct']);
        Route::get('{product}/images/{image}', [PublicProductImageController::class, 'ImageOfSpecificProduct']);


        Route::get('/variants/all', [PublicProductVariantController::class, 'index']);
        Route::get('/variants/{variant}', [PublicProductVariantController::class, 'show']);
        Route::get('/variants/search/{name}', [PublicProductVariantController::class, 'search']);
        Route::get('{product}/variants', [PublicProductVariantController::class, 'VariantsOfSpecificProduct']);
        Route::get('{product}/variants/{variant}', [PublicProductVariantController::class, 'VariantOfSpecificProduct']);

        Route::get('/discounts/all', [PublicProductDiscountController::class, 'index']);
        Route::get('/discounts/{discount}', [PublicProductDiscountController::class, 'show']);
        Route::get('{product}/discounts', [PublicProductDiscountController::class, 'DiscountsOfSpecificProduct']);
        Route::get('{product}/discounts/{discount}', [PublicProductDiscountController::class, 'DiscountOfSpecificProduct']);

        Route::get('/reviews/all', [PublicProductReviewController::class, 'index']);
        Route::get('/reviews/{review}', [PublicProductReviewController::class, 'show']);
        Route::get('{product}/reviews', [PublicProductReviewController::class, 'ReviewsOfSpecificProduct']);
        Route::get('{product}/reviews/{review}', [PublicProductReviewController::class, 'ReviewOfSpecificProduct']);
        Route::post('reviews/create', [PublicProductReviewController::class, 'store']);
        Route::patch('reviews/{id}', [PublicProductReviewController::class, 'update']);
        Route::delete('reviews/{id}', [VendorProductReviewController::class, 'delete']);

        Route::get('inventory/all', [ProductInventoryController::class, 'index']);
        Route::get('inventory/show/{id}', [ProductInventoryController::class, 'show']);
    });


Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:vendor'])
    ->prefix('vendor/products')
    ->group(function () {
        Route::post('/', [VendorProductController::class, 'store']);
        Route::put('{id}', [VendorProductController::class, 'update']);
        Route::put('update/bulk', [VendorProductController::class, 'updateBulk']);
        Route::delete('{id}', [VendorProductController::class, 'delete']);
        Route::get('delete/check/{id}', [VendorProductController::class, 'isSoftDeleted']);
        Route::delete('delete/bulk', [VendorProductController::class, 'deleteBulk']);
        Route::post('{id}', [VendorProductController::class, 'restore']);
        Route::post('restore/bulk', [VendorProductController::class, 'restoreBulk']);

        Route::post('images/upload', [VendorProductImageController::class, 'store']);
        Route::post('images/{id}', [VendorProductImageController::class, 'update']);
        Route::delete('images/{id}', [VendorProductImageController::class, 'delete']);
        Route::delete('images/delete-bulk/{product}', [VendorProductImageController::class, 'deleteAllProductImages']);

        Route::post('variants/create', [VendorProductVariantController::class, 'store']);
        Route::patch('variants/{id}', [VendorProductVariantController::class, 'update']);
        Route::patch('{product}/variants/update-bulk', [VendorProductVariantController::class, 'updateBulk']);
        Route::delete('variants/{id}', [VendorProductVariantController::class, 'delete']);
        Route::get('variants/delete-check/{id}', [VendorProductVariantController::class, 'isSoftDeleted']);
        Route::delete('{product}/variants/delete-bulk', [VendorProductVariantController::class, 'deleteAllProductVariants']);
        Route::post('variants/restore/{id}', [VendorProductVariantController::class, 'restore']);
        Route::post('{product}/variants/restore-bulk', [VendorProductVariantController::class, 'restoreAllProductVariants']);

        Route::post('discounts/create', [VendorProductDiscountController::class, 'store']);
        Route::patch('discounts/{id}', [VendorProductDiscountController::class, 'update']);
        Route::patch('{product}/discounts/update-bulk', [VendorProductDiscountController::class, 'updateBulk']);
        Route::patch('discounts/update-bulk/for-vendor', [VendorProductDiscountController::class, 'updateProductsDiscountsOfSpecificVendor']);

        Route::delete('discounts/{id}', [VendorProductDiscountController::class, 'delete']);
        Route::delete('{product}/discounts/delete-bulk', [VendorProductDiscountController::class, 'deleteAllProductDiscounts']);
        Route::delete('discounts/delete-bulk/for-vendor', [VendorProductDiscountController::class, 'deleteProductsDiscountsOfSpecificVendor']);
        Route::get('discounts/delete-check/{id}', [VendorProductDiscountController::class, 'isSoftDeleted']);
        Route::post('discounts/restore/{id}', [VendorProductDiscountController::class, 'restore']);
        Route::post('{product}/discounts/restore-bulk', [VendorProductDiscountController::class, 'restoreAllProductDiscounts']);
        Route::post('discounts/restore-bulk/for-vendor', [VendorProductDiscountController::class, 'restoreProductsDiscountsOfSpecificVendor']);

        Route::patch('reviews/{id}', [VendorProductReviewController::class, 'update']);
        Route::patch('{product}/reviews/update-bulk', [VendorProductReviewController::class, 'updateBulk']);
        Route::delete('reviews/{id}', [VendorProductReviewController::class, 'delete']);
        Route::delete('{product}/reviews/delete-bulk', [VendorProductReviewController::class, 'deleteAllProductReviews']);
        Route::get('reviews/delete-check/{id}', [VendorProductReviewController::class, 'isSoftDeleted']);
        Route::post('reviews/restore/{id}', [VendorProductReviewController::class, 'restore']);
        Route::post('{product}/reviews/restore-bulk', [VendorProductReviewController::class, 'restoreAllProductReviews']);

        Route::post('inventory/create', [ProductInventoryController::class, 'store']);
        Route::patch('inventory/{id}', [ProductInventoryController::class, 'update']);
        Route::patch('inventory/update/bulk', [ProductInventoryController::class, 'updateBulk']);
        Route::delete('inventory/{id}', [ProductInventoryController::class, 'delete']);
        Route::delete('inventory/delete/bulk', [ProductInventoryController::class, 'deleteBulk']);
        Route::delete('inventory/delete/{warehouse}/{product}', [ProductInventoryController::class, 'deleteProductFromSpecificWarehouse']);
    });

Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:vendor'])
    ->prefix('vendor/warehouses')
    ->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::get('{id}', [WarehouseController::class, 'show']);
        Route::get('search/where', [WarehouseController::class, 'search']);

        Route::post('/', [WarehouseController::class, 'store']);
        Route::patch('{id}', [WarehouseController::class, 'update']);
        Route::patch('update/bulk', [WarehouseController::class, 'updateBulk']);
        Route::delete('{id}', [WarehouseController::class, 'delete']);
        Route::delete('delete/bulk', [WarehouseController::class, 'deleteBulk']);
    });

Route::middleware(['auth:sanctum']) //middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin/warehouses')
    ->group(function () {
        Route::get('/', [WarehouseController::class, 'index']);
        Route::get('{id}', [WarehouseController::class, 'show']);
        Route::get('search/where', [WarehouseController::class, 'search']);

        Route::post('/', [WarehouseController::class, 'store']);
        Route::patch('{id}', [WarehouseController::class, 'update']);
        Route::patch('update/bulk', [WarehouseController::class, 'updateBulk']);
        Route::delete('{id}', [WarehouseController::class, 'delete']);
        Route::delete('delete/bulk', [WarehouseController::class, 'deleteBulk']);
    });




Route:: //middleware(['auth:sanctum', 'role:admin'])
    prefix('admin/products')
    ->group(function () {
        Route::get('/pendding', [AdminProductController::class, 'pendding']);
        Route::patch('/approve/{product}', [AdminProductController::class, 'approve']);
        Route::patch('/reject/{product}', [AdminProductController::class, 'reject']);
        Route::patch('/change-vendor/{old_vendor}/{new_vendor}', [AdminProductController::class, 'changeVendor']);

        Route::post('/statuses', [AdminProductStatusController::class, 'store']);
        Route::patch('/statuses/{id}', [AdminProductStatusController::class, 'update']);
        Route::delete('/statuses/{id}', [AdminProductStatusController::class, 'delete']);
    });


Route::middleware(['auth:sanctum'])
    ->prefix('public/carts')
    ->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::get('{id}', [CartController::class, 'show']);
        Route::post('/', [CartController::class, 'store']);
        Route::patch('{id}', [CartController::class, 'update']);
        Route::delete('{id}', [CartController::class, 'delete']);
        Route::get('delete/check/{id}', [CartController::class, 'isSoftDeleted']);
        Route::post('restore/{id}', [CartController::class, 'restore']);
    });


Route::middleware(['auth:sanctum'])
    ->prefix('public/wishlists')
    ->group(function () {
        Route::get('/', [WishlistController::class, 'index']);
        Route::get('{id}', [WishlistController::class, 'show']);
        Route::post('/', [WishlistController::class, 'store']);
        Route::patch('{id}', [WishlistController::class, 'update']);
        Route::delete('{id}', [WishlistController::class, 'delete']);
        Route::get('delete/check/{id}', [WishlistController::class, 'isSoftDeleted']);
        Route::post('restore/{id}', [WishlistController::class, 'restore']);
    });
#--------------------------------------------------------------------------------------------------------


Route::middleware(['auth:sanctum'])
    ->prefix('admin/currencies')
    ->group(function () {
        Route::post('/', [CurrencyController::class, 'store']);
        Route::patch('{id}', [CurrencyController::class, 'update']);
        Route::delete('{id}', [CurrencyController::class, 'delete']);
        Route::get('delete/check/{id}', [CurrencyController::class, 'isSoftDeleted']);
        Route::post('restore/{id}', [CurrencyController::class, 'restore']);
    });

Route::middleware(['auth:sanctum'])
    ->prefix('public/currencies')
    ->group(function () {
        Route::get('/', [CurrencyController::class, 'index']);
        Route::get('{id}', [CurrencyController::class, 'show']);
    });



Route::middleware(['auth:sanctum'])
    ->prefix('orders')
    ->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::get('{orderId}', [OrderController::class, 'show']);
        Route::post('/', [OrderController::class, 'store']);
        Route::patch('{orderId}', [OrderController::class, 'update']);

        Route::get('statuses/all', [OrderController::class, 'ordersStatuses']);
        Route::get('{orderId}/statuses', [OrderController::class, 'updateOrderStatus']);


        Route::get('all/items', [OrderItemController::class, 'index']);
        Route::get('{orderId}/items', [OrderItemController::class, 'show']);
        Route::get('{orderId}/items/{itemId}', [OrderItemController::class, 'show']);

        Route::post('{orderId}/items', [OrderItemController::class, 'store']);
        Route::patch('{orderId}/items/{itemId}', [OrderItemController::class, 'update']);
        Route::delete('{orderId}/items/{itemId}', [OrderItemController::class, 'delete']);
    });

#--------------------------------------------------------------------------------------------------------















Route::get('test', function (Request $request) {

    return $request->user()->role->name;
})->middleware('auth:sanctum');
