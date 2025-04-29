<?php

namespace App\Http\Controllers\Api\Vendor\ProductsWarehousesManagement;

use Exception;
use Illuminate\Http\Request;
use App\Models\Products\Product;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Warehouses\Warehouse;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductsWarehousesManagement\InventoryLocation;
use App\Models\ProductsWarehousesManagement\InventoryMovement;
use App\Models\ProductsWarehousesManagement\WarehouseInventory;
use App\Http\Requests\ProductsWarehousesManagement\StoreStockInRequest;
use App\Http\Requests\ProductsWarehousesManagement\StoreStockOutRequest;

class ManageProductsInWarehousesController extends Controller
{
    public function index(string $warehouseID)
    {
        $warehouse = Warehouse::find($warehouseID);

        if (Auth::user()->id !== $warehouse->vendor_id) {
            return ApiResponse::error('Vendor can only list his products in his warehouses');
        }

        $locations = InventoryLocation::where('warehouse_id', $warehouseID)->get();
        $summaries = WarehouseInventory::where('warehouse_id', $warehouseID)->get();
        $movements = InventoryMovement::where('warehouse_id', $warehouseID)->get();

        return ApiResponse::success([
            'locations' => $locations,
            'summaries' => $summaries,
            'movements' => $movements
        ], 'Details successfully.', 200);
    }

    public function allProductsInWarehouse(string $warehouseID)
    {
        $warehouse = Warehouse::find($warehouseID);

        if (Auth::user()->id !== $warehouse->vendor_id) {
            return ApiResponse::error('Vendor can only list his products in his warehouses');
        }
        
        return ApiResponse::success(WarehouseInventory::with(['warehouse', 'product', 'variation'])
            ->where('warehouse_id', $warehouseID)
            ->get());
    }

    public function specificProductInWarehouse(string $warehouseID, string $productID)
    {
        $warehouse = Warehouse::find($warehouseID);

        if (Auth::user()->id !== $warehouse->vendor_id) {
            return ApiResponse::error('Vendor can only list his products in his warehouses');
        }
        
        return ApiResponse::success(WarehouseInventory::with(['warehouse', 'product', 'variation'])
            ->where('warehouse_id', $warehouseID)
            ->where('product_id', $productID)
            ->get());
    }

    public function specificVariationInWarehouse(string $warehouseID, string $productID, string $variationID)
    {
        $warehouse = Warehouse::find($warehouseID);

        if (Auth::user()->id !== $warehouse->vendor_id) {
            return ApiResponse::error('Vendor can only list his products in his warehouses');
        }
        
        return ApiResponse::success(WarehouseInventory::with(['warehouse', 'product', 'variation'])
            ->where([
                ['warehouse_id', '=', $warehouseID],
                ['product_id', '=', $productID],
                ['variation_id', '=', $variationID],
            ])
            ->first()); // Use first() because a specific variation should be a single result
    }

    public function stockIn(StoreStockInRequest $request)
    {
        try {
            $data = $request->validated();

            $product = Product::find($data['product_id']);
            $warehouse = Warehouse::find($data['warehouse_id']);

            if ($request->user()->id !== $product->vendor_id && $request->user()->id !== $warehouse->vendor_id) {
                return ApiResponse::error('Vendor can only store his products in his warehouses');
            }

            $location = null;
            $summary = null;
            $movement = null;
            DB::transaction(function () use ($data, &$location, &$summary, &$movement) {
                // Fetch or create the bin-level inventory
                $location = InventoryLocation::firstOrNew([
                    'warehouse_id' => $data['warehouse_id'],
                    'bin_id' => $data['bin_id'],
                    'product_id' => $data['product_id'],
                    'variation_id' => $data['variation_id'],
                    'batch_number' => $data['batch_number'],
                    'expiry_date' => $data['expiry_date'],
                ]);

                $before = $location->quantity ?? 0;
                $location->quantity = $before + $data['quantity'];
                $location->save();

                // Update warehouse summary
                $summary = WarehouseInventory::firstOrNew([
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $data['product_id'],
                    'variation_id' => $data['variation_id'],
                ]);
                $summary->quantity_on_hand += $data['quantity'];
                $summary->low_stock_threshold = $data['low_stock_threshold'] ?? $summary->low_stock_threshold;
                $summary->save();

                // Record movement
                $movement = InventoryMovement::create([
                    'mover_id' => Auth::user()->id,
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id' => $data['product_id'],
                    'variation_id' => $data['variation_id'],
                    'movement_type' => $data['movement_type'],
                    'quantity_change' => $data['quantity'],
                    'quantity_before' => $before,
                    'quantity_after' => $location->quantity,
                    'notes' => $data['notes'] ?? null,
                ]);
            });

            return ApiResponse::success([
                'location' => $location,
                'summary' => $summary,
                'movement' => $movement
            ], 'Inventory added successfully.', 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error('error stock-in products in warehouse', 500);
        }
    }

    public function stockOut(StoreStockOutRequest $request)
    {
        try {
            $data = $request->validated();

            $locations = null;
            $summary = null;
            $movement = null;
            DB::transaction(function () use ($data, &$locations, &$summary, &$movement) {
                // 1. Deduct from FIFO bins
                $remaining = $data['quantity'];
                $locations = InventoryLocation::where('warehouse_id', $data['warehouse_id'])
                    ->where('product_id', $data['product_id'])
                    ->where('variation_id', $data['variation_id'])
                    ->where('quantity', '>', 0)
                    ->orderBy('expiry_date') // FEFO
                    ->orderBy('id')          // FIFO fallback
                    ->lockForUpdate()
                    ->get();

                $totalBefore = 0;
                $totalAfter = 0;

                foreach ($locations as $location) {
                    if ($remaining <= 0) break;

                    $deduct = min($remaining, $location->quantity);
                    $before = $location->quantity;

                    $location->quantity -= $deduct;
                    $location->save();

                    $remaining -= $deduct;
                    $totalBefore += $before;
                    $totalAfter += $location->quantity;

                    $movement = InventoryMovement::create([
                        'mover_id'         => Auth::user()->id,
                        'warehouse_id'     => $data['warehouse_id'],
                        'product_id'       => $data['product_id'],
                        'variation_id'     => $data['variation_id'],
                        'movement_type'    => $data['movement_type'],
                        'quantity_change'  => -$deduct,
                        'quantity_before'  => $before,
                        'quantity_after'   => $location->quantity,
                        'notes'            => $data['notes'] ?? null,
                    ]);
                }

                if ($remaining > 0) {
                    throw new Exception('Not enough stock to fulfill request.');
                }

                // 2. Adjust warehouse summary
                $summary = WarehouseInventory::where([
                    'warehouse_id' => $data['warehouse_id'],
                    'product_id'   => $data['product_id'],
                    'variation_id' => $data['variation_id'],
                ])->lockForUpdate()->firstOrFail();

                $summary->quantity_on_hand -= $data['quantity'];
                $summary->save();
            });

            return ApiResponse::success([
                'locations' => $locations,
                'summary' => $summary,
                'movement' => $movement
            ], 'Stock deducted and movement logged.', 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error('error stock-out products in warehouse', 500);
        }
    }

    public function updateSettings(Request $request, WarehouseInventory $inventory)
    {
        try {
            $data = $request->validate([
                'low_stock_threshold' => 'nullable|integer|min:0',
                'reorder_quantity'    => 'nullable|integer|min:0',
            ]);

            $inventory->update($data);

            return ApiResponse::success($inventory, 'Inventory updated successfully.', 201);
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return ApiResponse::error('error stock-in products in warehouse', 500);
        }
    }

    public function lowStock()
    {
        return ApiResponse::success(WarehouseInventory::whereColumn('quantity_on_hand', '<', 'low_stock_threshold')
            ->with(['warehouse', 'product', 'variation'])
            ->get());
    }
}