<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Table: warehouses
         * 
         * This table stores information about each physical or virtual warehouse used in the system.
         * A warehouse is typically owned or managed by a vendor (linked via `vendor_id`).
         * It includes key contact information, location details, operational status, and priority settings.
         * Warehouses can be activated/deactivated, soft deleted, and sorted by priority for fulfillment logic.
         * 
         * Key Use Cases:
         * - Assigning fulfillment centers for orders.
         * - Grouping and routing products based on geographic proximity.
         * - Contacting warehouse managers or support teams.
         * - Performing audits and managing active/inactive warehouse facilities.
         * - Filtering and sorting for inventory, logistics, and reporting purposes.
         */
        Schema::create('warehouses', function (Blueprint $table) {
            // Unique warehouse ID (primary key).
            $table->id();
            // Foreign key linking to the users table, indicating the owner/vendor of the warehouse. Deletes warehouse if vendor is deleted.
            $table->foreignUuid('vendor_id')->constrained('users')->onDelete('cascade');
            // Short, unique identifier (like WH-001) used internally or for logistics partners.
            $table->string('code', 10)->unique();
            // Essential contact details for managing the warehouse. Useful for support, driver coordination, etc.
            $table->string('name');
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone', 20);
            // total capacity:
            $table->integer('total_capacity');
            // Foreign key to the cities table. Restrict delete to avoid orphaning data if a city is deleted.
            $table->foreignId('city_id')->constrained('cities')->onDelete('restrict');
            // to activate/deactivate a warehouse. Disabling a warehouse may prevent routing orders to it.
            $table->enum('status', ['active', 'maintenance', 'retired'])->default('active');
            // Optional GPS coordinates. Useful for mapping, route optimization, and distance-based delivery.
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            // Determines the preference of this warehouse in stock fulfillment (e.g., use local warehouse first). Lower values = higher priority.
            $table->unsignedInteger('priority')->default(0);
            // Tracks creation/update times and allows soft deletion for potential data recovery or auditing.
            $table->timestamps();
            // Improve lookup speed for common filters like code, is_active, priority.
            $table->index('code');
            $table->index('priority');
        });

        /**
         * Table: warehouse_zones
         * 
         * Represents logical divisions within a warehouse, often used to improve organization, 
         * navigation, and inventory management. A zone might represent a specific area like 
         * "Receiving Zone", "Cold Storage", or "Zone A".
         * 
         * Key Use Cases:
         * - Simplifies locating items by narrowing down to sections within a warehouse.
         * - Enables strategic grouping (e.g., temperature-controlled zones).
         * - Enhances operational efficiency for pickers, stockers, and automation systems.
         * - Supports granular reporting and planning within a warehouse.
         * 
         * Constraints:
         * - Each zone belongs to one warehouse.
         * - Code must be unique within its warehouse.
         */
        Schema::create('warehouse_zones', function (Blueprint $table) {
            $table->id();
            // Foreign key to the warehouse.
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            // Short unique code per zone (RZ01, FRZ, etc.).
            $table->string('code', 10);
            // Human-readable name.
            $table->string('name');
            $table->enum('status', ['active', 'maintenance', 'retired'])->default('active');
            $table->timestamps();
            // Code must be unique within a warehouse.
            $table->unique(['warehouse_id', 'code']);
            $table->index('warehouse_id');
        });

        /**
         * Table: warehouse_racks
         * 
         * Represents racks or vertical structures within a warehouse zone, 
         * typically used to hold shelves and bins for organized storage.
         * Racks are grouped under zones, forming the second level of the warehouse layout hierarchy.
         * 
         * Key Use Cases:
         * - Allows fine-grained physical structuring of the warehouse space.
         * - Facilitates organized and scalable storage.
         * - Enhances the efficiency of stock retrieval and placement.
         * - Forms part of the full inventory location path (e.g., Zone A → Rack 1 → Shelf B).
         * 
         * Constraints:
         * - Each rack belongs to a specific zone.
         * - Rack codes must be unique within the same zone.
         */
        Schema::create('warehouse_racks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zone_id')->constrained('warehouse_zones')->onDelete('cascade');
            $table->string('code', 10);
            $table->string('name');
            $table->enum('status', ['active', 'maintenance', 'retired'])->default('active');
            $table->timestamps();

            $table->unique(['zone_id', 'code']);
            $table->index('zone_id');
        });

        /**
         * Table: warehouse_shelves
         * 
         * Represents horizontal shelf levels within a specific rack. Shelves are the third level
         * in the warehouse layout hierarchy (Warehouse → Zone → Rack → Shelf), and they organize
         * bins or storage spaces vertically within racks.
         * 
         * Key Use Cases:
         * - Breaks down rack space into logical levels for more precise product placement.
         * - Enhances navigation and storage accuracy for warehouse staff.
         * - Supports sort ordering for visual or operational prioritization (e.g., top-to-bottom).
         * - Allows full location pathing (e.g., Zone A → Rack 2 → Shelf B).
         * 
         * Constraints:
         * - Each shelf belongs to a specific rack.
         * - Shelf codes must be unique within the same rack.
         */
        Schema::create('warehouse_shelves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rack_id')->constrained('warehouse_racks')->onDelete('cascade');
            $table->string('code', 10);
            $table->string('name');

            $table->enum('status', ['active', 'maintenance', 'retired'])->default('active');
            $table->timestamps();

            $table->unique(['rack_id', 'code']);
            $table->index('rack_id');
        });

        /**
         * Table: warehouse_bins
         * 
         * Represents the most granular physical storage unit within a warehouse: bins.
         * Bins are small, assignable spaces located on shelves, where actual products are stored.
         * This table defines storage characteristics and constraints for each bin.
         * 
         * Key Use Cases:
         * - Supports precise inventory placement and picking.
         * - Helps optimize bin utilization based on dimensions and weight limits.
         * - Facilitates bin-type categorization for automation or warehousing rules (e.g., only bulky items in 'pallet' bins).
         * - Enables activation/deactivation of bins for maintenance or space restructuring.
         * 
         * Hierarchical Path: Warehouse → Zone → Rack → Shelf → Bin
         * 
         * Constraints:
         * - Each bin belongs to a specific shelf.
         * - Bin codes must be unique within a shelf.
         * 
         * Metadata:
         * - Width, height, depth, and max_weight define physical capacity for bin allocation logic.
         * - is_active flag determines whether a bin is currently usable.
         */
        Schema::create('warehouse_bins', function (Blueprint $table) {
            $table->id();
            // Shelf where this bin lives.
            $table->foreignId('shelf_id')->constrained('warehouse_shelves')->onDelete('cascade');
            $table->string('code', 10);
            $table->string('name');
            // Useful for categorizing bins (size/type)
            $table->enum('bin_type', ['small', 'medium', 'large', 'pallet', 'bulk']);
            // Physical limits — helps when assigning items.
            // Capacity dimensions
            $table->unsignedMediumInteger('volume_cm3')->virtualAs('width*height*depth');
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('depth', 8, 2)->nullable();
            $table->decimal('max_weight', 8, 2)->nullable();
            // For deactivating unused or damaged bins.
            $table->enum('status', ['active', 'maintenance', 'retired'])->default('active');
            $table->timestamps();

            $table->unique(['shelf_id', 'code']);
            $table->index('shelf_id');
            $table->index('bin_type');
        });

        Schema::create('warehouse_operating_hours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->enum('day_of_week', ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']);
            $table->time('open_time');
            $table->time('close_time');
            $table->timestamps();
        });
        
        Schema::create('warehouse_equipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('name');
            $table->enum('type', ['forklift', 'conveyor', 'sorter', 'robot']);
            $table->json('specifications');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_equipments');
        Schema::dropIfExists('warehouse_operating_hours');
        Schema::dropIfExists('warehouse_bins');
        Schema::dropIfExists('warehouse_shelves');
        Schema::dropIfExists('warehouse_racks');
        Schema::dropIfExists('warehouse_zones');
        Schema::dropIfExists('warehouses');
    }
};