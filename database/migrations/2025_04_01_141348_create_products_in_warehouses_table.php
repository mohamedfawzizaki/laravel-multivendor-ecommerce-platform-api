<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     **/
    public function up(): void
    {
        /**
         * Table: inventory_locations
         * 
         * Represents the exact physical and logical placement of a specific product (and variation) in a warehouse.
         * This table enables precise inventory tracking across bins, batches, and expiry dates.
         * 
         * Key Use Cases:
         * - Tracks how much of a product exists in a specific bin of a warehouse.
         * - Enables batch-level and expiry-level stock management for products requiring traceability.
         * - Supports FIFO/FEFO picking strategies in fulfillment workflows.
         * - Allows inventory queries such as "how many items are in bin X?" or "where is batch Y stored?"
         * 
         * Relationships:
         * - Links to `warehouses` (for location context).
         * - Links to `warehouse_bins` (most granular physical location, nullable to allow non-bin-specific storage).
         * - Links to `products` and optionally `product_variations`.
         * 
         * Constraints:
         * - Composite unique key ensures no duplicate product-batch entries in the same bin/warehouse.
         * 
         * Metadata:
         * - `batch_number` and `expiry_date` are useful for industries like pharmaceuticals, cosmetics, food, etc.
         * - Allows granular per-bin tracking without duplication or ambiguity.
         */
        Schema::create('inventory_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('bin_id')->nullable()->constrained('warehouse_bins')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            // Current quantity in this location.
            $table->unsignedInteger('quantity');
            // For batch control or perishable inventory (like food, pharma).
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            // Prevent duplicate entries for the same item in same bin, batch, and expiry.
            $table->unique(
                ['warehouse_id', 'bin_id', 'product_id', 'variation_id', 'batch_number', 'expiry_date'],
                'inventory_location_unique'
            );
            // Useful indexes for fast lookups.
            $table->index('warehouse_id');
            $table->index('bin_id');
            $table->index('product_id');
            $table->index('variation_id');
            $table->index('batch_number');
            $table->index('expiry_date');
        });

        /**
         * Table: warehouse_inventory
         *
         * This table keeps a **summary view** of a product’s stock at the **warehouse level**.
         * It doesn’t store individual bin-level or batch-level details — that would be handled in `inventory_locations`.
         *
         * Primary Use Cases:
         * - Quickly check how much stock is available at a warehouse for a specific product.
         * - Enable order allocation, stock availability checks, and reordering.
         * - Useful for dashboards, reorder alerts, and syncing with external systems like ERPs.
         *
         * Relationships:
         * - Each row is linked to a warehouse, a product, and optionally a variation (like color/size).
         *
         * Key Fields:
         * - `quantity_on_hand`: Actual usable stock currently in the warehouse.
         * - `quantity_allocated`: Reserved stock — tied to orders but not yet shipped.
         * - `quantity_on_hold`: Temporarily blocked inventory (e.g., under inspection or damaged).
         *
         * Inventory Control:
         * - `low_stock_threshold`: Optional value for triggering low stock warnings.
         * - `reorder_quantity`: Helps automation decide how much to reorder when replenishment is triggered.
         *
         * Extra:
         * - `location_code`: Simple reference to a shelf/bin location (e.g., A-01-B), not normalized.
         * - Unique constraint ensures there’s only one inventory row per warehouse/product/variation combo.
         */
        Schema::create('warehouse_inventory', function (Blueprint $table) {
            $table->id();
            // Warehouse that holds the stock.
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            // Product being stored.
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            // Optional variation (e.g., size, color).
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            // Total usable stock in this warehouse.
            $table->unsignedInteger('quantity_on_hand')->default(0);
            // Stock reserved for confirmed orders.
            $table->unsignedInteger('quantity_allocated')->default(0);
            // Held back due to damage, inspection, etc.
            $table->unsignedInteger('quantity_on_hold')->default(0);
            // When stock drops below this, trigger a low stock alert.
            $table->unsignedInteger('low_stock_threshold')->nullable();
            // Minimum quantity to reorder.
            $table->unsignedInteger('reorder_quantity')->nullable();
            // Optional bin/shelf notation (A-01-B).
            $table->string('location_code', 50)->nullable();
            $table->timestamps();
            // One inventory record per product/variation in each warehouse.
            $table->unique(['warehouse_id', 'product_id', 'variation_id'], 'warehouse_product_unique');
            // Speed up queries for key fields.
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('variation_id');
            $table->index('location_code');
        });

        /**
         * Table: inventory_movements
         *
         * This table logs every **stock change event** (both inbound and outbound) at the product level.
         * It's essential for **audit trails**, **inventory reconciliation**, and **reporting on stock activity**.
         *
         * Core Purpose:
         * - Provide a **chronological record** of all stock movements — who did what, when, and how it affected stock levels.
         * - Serves as a **source of truth** when investigating stock discrepancies, loss, or unexpected changes.
         *
         * Key Fields:
         * - `movement_type`: Defines the reason for the movement (purchase, sale, return, adjustment, etc.).
         * - `quantity_change`: The delta — can be **positive or negative**.
         * - `quantity_before` / `quantity_after`: Captures inventory level change for historical context.
         * - `mover_id`: User responsible for the change (could be staff, admin, vendor).
         *
         * Example Use Cases:
         * - Logging stock-in from a supplier (purchase).
         * - Subtracting stock for a customer order (sale).
         * - Manually adjusting count due to physical mismatch (adjustment).
         * - Tracking inter-warehouse transfers (transfer_in / transfer_out).
         * - Recording lost or found inventory during audits.
         *
         * Why It's Critical:
         * - Enables **inventory integrity** checks.
         * - Useful for accountability, loss tracking, and forecasting.
         * - Can help automate reports like "Top reasons for stock loss this month".
         */
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            // User who performed the movement. Nullable if deleted.
            $table->uuid('mover_id')->nullable();
            $table->foreign('mover_id')->references('id')->on('users')->nullOnDelete();

            // The warehouse where the movement took place.
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            // The product affected.
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            // Optional variant (e.g., size/color).
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            // Type of inventory movement.
            $table->enum('movement_type', [
                'purchase',      // stock added from a supplier
                'sale',          // stock shipped out to customer       
                'return',        // customer returned item   
                'adjustment',    // manual correction (e.g. stock count mismatch)
                'transfer_in',   // received from another warehouse
                'transfer_out',  // sent to another warehouse or lost/damaged
                'loss',          // inventory lost, stolen, or missing
                'found'          // unexpected inventory found during audit
            ]);
            // The actual change in quantity (can be negative).
            $table->integer('quantity_change');
            // Stock level before the change.
            $table->unsignedInteger('quantity_before');
            // Stock level after the change.
            $table->unsignedInteger('quantity_after');
            // Optional freeform notes.
            $table->text('notes')->nullable();
            $table->timestamps();
            // Common indexes for reporting and filtering.
            $table->index('warehouse_id');
            $table->index('product_id');
            $table->index('variation_id');
            $table->index('movement_type');
        });

        /**
         * Table: warehouse_transfers
         * 
         * Represents a stock transfer transaction between two warehouses within the organization.
         * Enables tracking, planning, and auditing of inventory movement from one physical warehouse to another.
         * 
         * Key Use Cases:
         * - Internal stock replenishment between regional warehouses or distribution centers.
         * - Optimizing stock levels across locations based on demand.
         * - Tracking the lifecycle and accountability of stock transfers (requested, approved, shipped, completed).
         * 
         * Relationships:
         * - `from_warehouse_id` and `to_warehouse_id` link to the source and destination warehouses.
         * - `created_by` and `approved_by` link to user accounts for audit trails.
         * 
         * Lifecycle:
         * - Starts as `draft` → moved to `pending` once submitted → `in_transit` after shipment → `completed` when stock is received.
         * - `cancelled` status halts the process.
         * 
         * Metadata:
         * - Transfer number is a unique human-readable identifier (e.g., TRF-2025-0012).
         * - Timestamps allow scheduling and historical tracking.
         */
        Schema::create('warehouse_transfers', function (Blueprint $table) {
            $table->id();
            // Unique ID (e.g., TRF-000123) for tracking.
            $table->string('transfer_number')->unique();
            // Status of the transfer: draft, pending, in_transit, completed, cancelled.
            $table->enum('status', ['draft', 'pending', 'in_transit', 'completed', 'cancelled'])->default('draft');
            // Origin and destination warehouses.
            $table->foreignId('from_warehouse_id')->constrained('warehouses')->onDelete('restrict');
            $table->foreignId('to_warehouse_id')->constrained('warehouses')->onDelete('restrict');
            // Freeform field for admin notes (e.g., "Urgent restock for Black Friday").
            $table->text('notes')->nullable();
            // Users responsible for creating/approving the transfer.
            $table->uuid('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->uuid('approved_by')->nullable();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();

            // Scheduled shipping/pickup date.
            $table->timestamp('expected_transfer_date')->nullable();
            // Timestamp of completion.
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            // Indexes for performance on filters, sorting, and querying.
            $table->index('transfer_number');
            $table->index('status');
            $table->index('from_warehouse_id');
            $table->index('to_warehouse_id');
            $table->index('created_at');
        });

        /**
         * Table: warehouse_transfer_items
         * 
         * Stores the individual line items associated with a warehouse transfer.
         * Each row represents a specific product (and optionally a variation) being moved from one warehouse to another.
         * 
         * Key Use Cases:
         * - Enables detailed tracking of inventory quantities during inter-warehouse transfers.
         * - Captures discrepancies between planned vs. actual movement (sent vs. received).
         * - Supports batch-controlled and perishable items (batch number + expiry date).
         * 
         * Relationships:
         * - Linked to `warehouse_transfers` via `transfer_id` (parent transfer document).
         * - Links to `products` and optionally `product_variations` (e.g., size, color).
         * 
         * Quantity Fields:
         * - `quantity_requested`: How much was requested to be transferred.
         * - `quantity_sent`: How much was actually shipped from the source warehouse.
         * - `quantity_received`: How much was confirmed received at the destination warehouse.
         * These fields help identify shortages, losses, or transit issues.
         * 
         * Batch & Expiry:
         * - Used to track perishable or regulated inventory (e.g., pharmaceuticals, food).
         */
        Schema::create('warehouse_transfer_items', function (Blueprint $table) {
            $table->id();
            // Link to the parent transfer document.
            $table->foreignId('transfer_id')->constrained('warehouse_transfers')->onDelete('cascade');
            // Product being transferred.
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade');
            // Planned quantity to be transferred.
            $table->unsignedInteger('quantity_requested');
            // Actual quantity sent from the origin.
            $table->unsignedInteger('quantity_sent')->default(0);
            // Actual quantity received at the destination.
            $table->unsignedInteger('quantity_received')->default(0);
            // Batch tracking — optional.
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            // Indexes for faster lookups and filtering.
            $table->index('transfer_id');
            $table->index('product_id');
            $table->index('variation_id');
        });
    }

    /**
     * Here’s an **exact, end-to-end inventory workflow** for an e-commerce website based on the schema you’ve defined for:

    - `inventory_locations` (granular bin/batch stock)
    - `warehouse_inventory` (warehouse-level summary)
    - `inventory_movements` (auditable stock changes)
    ### ✅ **Inventory Workflow Breakdown**
    ## 🟢 1. **Inbound Flow – Stock Arrival (e.g., Purchase from Supplier)**
        **Event:** New stock arrives at warehouse (e.g., Purchase Order received)
    ### Workflow:
    1. **Receive and inspect stock** at warehouse dock.
    2. **Assign to warehouse + bin + batch/expiry** (if applicable).
    3. **Insert into `inventory_locations`:**
        - Specify `warehouse_id`, `bin_id`, `product_id`, `variation_id`, `batch_number`, `expiry_date`, and `quantity`.
    4. **Update `warehouse_inventory`:**
        - If record exists → increment `quantity_on_hand`
        - If not → create new record with initial `quantity_on_hand`
    5. **Insert `inventory_movements`:**
        - `movement_type`: `purchase`
        - `quantity_change`: +N
        - `quantity_before` / `quantity_after`: pulled from and stored for traceability
        - Optional: record `mover_id` (user who received goods)
    ## 🟡 2. **Reservation Flow – Customer Places Order**
        **Event:** A customer places an order online.
    ### Workflow:

    1. **Check availability** in `warehouse_inventory`:
        - Use `quantity_on_hand - quantity_allocated - quantity_on_hold` to determine if sufficient stock exists.
    2. **Allocate stock:**
        - Increment `quantity_allocated` in `warehouse_inventory` by order quantity.
    3. ✅ No change to `inventory_locations` or `inventory_movements` yet — this happens at fulfillment.
    ## 🔵 3. **Outbound Flow – Order Fulfillment (Picking & Shipping)**
        **Event:** Warehouse staff picks and ships the order.
    ### Workflow:
    1. **Pick from bins using FIFO/FEFO**:
        - Query `inventory_locations` ordered by `expiry_date` or `created_at`.
        - Deduct quantities from one or more bins as needed.
    2. **Update `inventory_locations`:**
        - Decrease `quantity` per bin.
    3. **Update `warehouse_inventory`:**
        - Decrease `quantity_on_hand` by picked amount.
        - Decrease `quantity_allocated` by shipped amount.
    4. **Insert `inventory_movements`:**
        - `movement_type`: `sale`
        - `quantity_change`: -N
        - Log bin-specific details in `notes` or link to a separate picking record.
    ## 🔄 4. **Return Flow – Customer Return**
        **Event:** Returned item(s) from customer.
    ### Workflow:
    1. **Inspect returned stock** (resellable? damaged?).
    2. If resellable:
        - Add back to `inventory_locations` (possibly different bin).
        - Increase `quantity_on_hand` in `warehouse_inventory`.
    3. **Insert `inventory_movements`:**
        - `movement_type`: `return`
        - `quantity_change`: +N
        - Capture bin + batch if added back.
    4. If not resellable:
        - Do not add to `quantity_on_hand`.
        - Log it as `loss` or `on_hold`.
    ## 🔁 5. **Inter-Warehouse Transfer**
        **Event:** Transfer stock from Warehouse A to B.
    ### Workflow:
    1. **From Source Warehouse:**
        - Reduce `inventory_locations` from relevant bins.
        - Update `warehouse_inventory.quantity_on_hand` (decrease).
        - Log `inventory_movements` as `transfer_out`.
    2. **To Destination Warehouse:**
        - Create/update `inventory_locations` for bins.
        - Update `warehouse_inventory.quantity_on_hand` (increase).
        - Log `inventory_movements` as `transfer_in`.
    ## 🔍 6. **Manual Adjustments / Audits**
        **Event:** Physical count reveals mismatch or damage.
    ### Workflow:
    1. **Compare expected vs actual stock.**
    2. If overage (unexpected stock found):
        - Update `inventory_locations` with additional quantity.
        - Increase `warehouse_inventory.quantity_on_hand`.
        - Log `inventory_movements`: `found`.
    3. If shortage (missing/damaged stock):
        - Decrease `inventory_locations` and/or move quantity to `quantity_on_hold`.
        - Decrease `warehouse_inventory.quantity_on_hand`.
        - Log `inventory_movements`: `loss` or `adjustment`.
    ### 🔐 Data Integrity Rules:
        - Always wrap stock updates in transactions.
        - Ensure consistency between `inventory_locations`, `warehouse_inventory`, and `inventory_movements`.
        - Use `inventory_movements` as your audit trail. Never skip it.
     */
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_transfer_items');
        Schema::dropIfExists('warehouse_transfers');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('warehouse_inventory');
        Schema::dropIfExists('inventory_locations');
    }
};