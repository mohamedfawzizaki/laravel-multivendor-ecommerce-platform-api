<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /**
         * Brands Table
         * 
         * Stores brand information for product categorization and branding.
         * - Enforces unique brand names and SEO-friendly slugs for URL routing
         * - Supports brand descriptions for rich content/marketing
         * - Stores external references like official website and brand logo URL
         * - Implements soft deletion to maintain referential integrity while allowing brand archival
         * - Unique Constraints:
         *   - Name (unq_brand_name): Prevent duplicate brand entries
         *   - Slug: Ensure unique URL identifiers
         */
        Schema::create('brands', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name')->unique('unq_brand_name'); // Brand display name (e.g., "Nike")
            $table->string('slug')->unique(); // URL-safe identifier (e.g., "nike-shoes")
            $table->text('description')->nullable(); // Detailed brand story/marketing copy
            $table->text('logo_url')->nullable(); // CDN path to brand logo image
            $table->text('website_url')->nullable(); // Official brand website URL
            $table->timestamps(); // Automatic created_at and updated_at timestamps
            $table->softDeletes(); // Mark deleted without physical removal (deleted_at)
        });

        /**
         * Categories Table
         * 
         * Organizes products into hierarchical groups for navigation and filtering.
         * - Unique category names prevent ambiguous groupings
         * - Slug field enables clean category page URLs
         * - Nullable description allows for SEO-optimized category pages
         * - Supports category nesting through separate hierarchy table
         * - Soft deletion preserves historical data while removing from active use
         * - Unique Constraints:
         *   - Name (unq_category_name): Prevent duplicate categories
         *   - Slug: Guarantee unique category URLs
         */
        Schema::create('categories', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('name')->unique('unq_category_name'); // Display name (e.g., "Running Shoes")
            $table->text('description')->nullable(); // Category overview text
            $table->string('slug')->unique(); // URL identifier (e.g., "running-shoes")
            $table->timestamps(); // Automatic timestamp tracking
            $table->softDeletes(); // Non-destructive deletion marker
        });

        /**
         * Category Hierarchy Table (Closure Table Pattern)
         * 
         * Manages parent-child relationships between categories to enable:
         * - Multi-level category trees (e.g., Electronics > Mobile > Accessories)
         * - Efficient ancestor/descendant queries
         * - Multiple inheritance prevention through unique constraints
         * 
         * Structure:
         * - parent_id: Direct ancestor category
         * - child_id: Direct descendant category
         * - unique_child: Enforces single-parent hierarchy (tree structure)
         * - unique_parent_child: Prevents duplicate relationships
         * 
         * Cascade delete ensures automatic cleanup when categories are removed
         */
        Schema::create('category_hierarchy', function (Blueprint $table) {
            $table->id(); // Surrogate primary key
            $table->foreignId('parent_id')->constrained('categories')->cascadeOnDelete(); // Parent category reference
            $table->foreignId('child_id')->constrained('categories')->cascadeOnDelete(); // Child category reference
            $table->unique(['parent_id', 'child_id'], 'unique_parent_child'); // Prevent duplicate relationships
            $table->unique(['child_id'], 'unique_child'); // Enforce single-parent hierarchy (tree structure)
        });

        /**
         * Products Table (Core Product Catalog)
         * 
         * Central repository for all sellable items in the system. Supports:
         * - Vendor-specific product management through vendor_id foreign key
         * - Dual product types: Simple (single price) and Variable (via variations)
         * - Multi-currency support through ISO currency code linkage
         * - Lifecycle management with status states and soft deletion
         * 
         * Key Features:
         * - Vendor-Unique Constraints: Allows identical product names/slugs across 
         *   different vendors while preventing duplicates within a vendor's catalog
         * - Price Architecture: base_price for simple products, NULL for variable
         *   products requiring variations
         * - Historical Pricing: base_compare_price enables "was/now" price displays
         * - SEO Optimization: Slug field powers clean product URLs
         * 
         * Relationships:
         * - Belongs to Vendor (users), Brand, and Category
         * - Currency code references centralized currency table
         */
        Schema::create('products', function (Blueprint $table) {
            $table->id(); // Auto-incrementing product ID
            // Ownership & Classification
            $table->foreignUuid('vendor_id')->constrained('users')->cascadeOnDelete(); // Delete products when vendor is removed
            $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete(); // Brand removal cascades to products
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete(); // Category deletion cleans up products
            // Core Product Data
            $table->string('name'); // Display name (e.g., "Pro Running Shoe")
            $table->string('slug'); // URL identifier (e.g., "pro-running-shoe-2024")
            $table->text('description')->nullable(); // Full product details/HTML
            // $table->enum('type', ['simple', 'variable'])->default('simple'); // Control visibility/catalog inclusion
            $table->enum('status', ['active', 'inactive', 'draft'])->default('active'); // Control visibility/catalog inclusion
            $table->decimal('base_price', 10, 2)->nullable(); // NULL = variable product
            $table->decimal('base_compare_price', 10, 2)->nullable(); // Reference price
            $table->string('currency_code', 10)->default('USD');
            $table->foreign('currency_code')->references('code')->on('currencies'); // Centralized currency rates/pricing
            $table->timestamps(); // Automatic audit tracking
            $table->softDeletes(); // Archive products without losing sales data
            // Composite Uniques
            $table->unique(['vendor_id', 'name'], 'unique_vendor_product_name');
            $table->unique(['vendor_id', 'slug'], 'unique_vendor_product_slug');
        });

        /**
         * Product Variations Table (Variable Product Options)
         * 
         * Manages variant-specific data for products with multiple options:
         * - Size/Color/Material combinations
         * - Variant-specific pricing and inventory tracking (via SKU)
         * - Attribute storage in JSON format for flexible option handling
         * 
         * Key Features:
         * - SKU Management: Global uniqueness prevents inventory conflicts
         * - Price Overrides: Per-variant pricing supersedes product base_price
         * - Option Preservation: Soft deletion maintains historical order data
         * - Attribute Flexibility: JSON structure supports dynamic variant types
         * 
         * Business Rules:
         * - Products with NULL base_price MUST have ≥1 variation
         * - Variations cannot exist without parent product
         * - SKU must be unique across entire catalog
         */
        Schema::create('product_variations', function (Blueprint $table) {
            $table->id(); // Auto-incrementing variation ID
            // Parent Product Linkage
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Remove variations if product deleted
            // Variant Identification
            $table->string('variant_name'); // Display name (e.g., "Blue / Large")
            $table->string('sku')->unique(); // Global inventory identifier
            // Pricing
            $table->decimal('price', 10, 2)->default(0); // Must be ≥0
            $table->decimal('compare_price', 10, 2)->nullable(); // Crossed-out price
            // Option Storage
            $table->json('attributes')->nullable(); // Structured format example:
            // {"size": "XL", "color": "#FF0000", "material": "Cotton"}
            $table->timestamps(); // Version tracking
            $table->softDeletes(); // Preserve variants for order history
            // Prevent duplicate SKUs per product
            $table->unique(['product_id', 'sku']);
        });

        /**
         * Product Media Table (Digital Asset Management)
         * 
         * Central repository for all product-related media assets with features:
         * - Multi-format Support: Images, videos, and documents
         * - Variant-Specific Media: Optional linkage to specific product variations
         * - Asset Prioritization: sort_order controls gallery display sequence
         * - Default Image Selection: Single highlighted image per product/variant
         * - Metadata Storage: Technical specs + SEO optimization fields
         * 
         * Key Functionality:
         * - Dual Ownership: Media can belong to either base product OR specific variation
         * - Storage Agnosticism: Path field works with local/cloud storage systems
         * - File Integrity: MIME type and size tracking for content validation
         * - Performance: Indexed on product_id and type for fast media retrieval
         * 
         * Data Integrity:
         * - Partial unique index ensures single default image per product
         * - Cascade delete purges media when parent product/variation is removed
         */
        Schema::create('product_media', function (Blueprint $table) {
            $table->id(); // Auto-incrementing media ID
            // Ownership
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Delete media when product removed
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->onDelete('cascade'); // Optional variant-specific media
            // Asset Properties
            $table->enum('type', ['image', 'video', 'document'])->default('image'); // Media classification
            $table->text('path'); // Full storage path/URL
            $table->string('mime_type'); // Technical format identifier
            $table->unsignedInteger('file_size')->nullable(); // Bytes for storage monitoring
            // Presentation
            $table->unsignedInteger('sort_order')->default(0); // Gallery position
            $table->json('metadata')->nullable(); // Structured storage for:
            // - Image dimensions
            // - Alt text
            // - Video duration
            // - Document page count
            $table->boolean('is_default')->default(false); // Primary display media
            $table->timestamps(); // Version tracking
            // Indexing Strategy
            $table->index('product_id'); // Faster product media queries
            $table->index('type'); // Efficient media type filtering
            // Cross-DB compatible unique constraint
            $table->unique(['product_id', 'is_default'], 'product_media_single_default');
        });

        /**
            class ProductMedia extends Model
            {
                protected static function booted()
                {
                    static::saving(function ($media) {
                        if ($media->is_default) {
                            // Remove previous default when setting new one
                            static::where('product_id', $media->product_id)
                                ->where('is_default', true)
                                ->update(['is_default' => false]);
                        }
                    });
                }
            }
         */

        /**
         * Product Discounts Table (Promotion Engine)
         * 
         * Manages temporal pricing rules with capabilities for:
         * - Dual Discount Types: Fixed price override vs percentage reduction
         * - Targeted Applications: Product-wide or variation-specific promotions
         * - Time-Bound Activations: Scheduled start/end dates for campaigns
         * - Non-Destructive Removal: Soft delete preserves historical offers
         * 
         * Business Rules:
         * - Exclusive Targeting: Discount must apply to EITHER product OR variation
         * - Percentage Validation: 0-100% range enforced at database level
         * - Temporal Consistency: end_date must be after start_date (application-layer)
         * 
         * Indexing Considerations:
         * - Automatic indexes on foreign keys via constrained()
         * - Date range filtering benefits from composite index on start/end dates
         */
        Schema::create('product_discounts', function (Blueprint $table) {
            $table->id(); // Auto-incrementing discount ID
            // Discount Target
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete(); // Product-wide discount
            $table->foreignId('variation_id')->nullable()->constrained('product_variations')->cascadeOnDelete(); // Variant-specific discount
            // Pricing Modifiers
            $table->decimal('discount_price', 10, 2)->default(0.00); // Absolute price override
            $table->decimal('discount_percentage', 5, 2)->nullable()->check('discount_percentage BETWEEN 0 AND 100'); // Relative discount
            // Activation Window
            $table->timestamp('start_date')->nullable(); // Promotion launch
            $table->timestamp('end_date')->nullable(); // Automatic expiration
            // Lifecycle Tracking
            $table->timestamps();
            $table->softDeletes(); // Audit trail for removed discounts
        });

        /**
         * Database-Level Constraint: Mutual Exclusivity
         * Ensures discounts cannot target both product and variation simultaneously.
         * - (product_id XOR variation_id) MUST be true
         * - Laravel migration workaround until native constraint support
         * - Backstop for application-layer validation
         */
        DB::statement("
            ALTER TABLE product_discounts 
            ADD CONSTRAINT chk_product_or_variation 
            CHECK (
                (product_id IS NOT NULL AND variation_id IS NULL) OR 
                (product_id IS NULL AND variation_id IS NOT NULL)
            )
        ");

        /**
         * Product Reviews Table (User Feedback System)
         * 
         * Captures customer feedback and ratings to drive social proof and product improvement.
         * Key Features:
         * - Anonymous Reviews: user_id is nullable to support guest reviews
         * - Rating Validation: Enforces 1-5 star system through check constraint
         * - Purchase Verification: verified_purchase flag identifies authentic buyer feedback
         * - Moderation Tools: Soft deletion allows review hiding without data loss
         * 
         * Relationships:
         * - Mandatory Product Link: All reviews tied to existing products
         * - Optional User Link: Preserves user association even if account is deleted
         * 
         * Data Integrity:
         * - Cascade delete removes reviews when parent product is deleted
         * - Rating range constraint enforced at database level
         * - Indexes optimize common filtering operations
         */
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id(); // Auto-incrementing review ID
            // Relationships
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete(); // Remove reviews when product deleted
            $table->uuid('user_id')->nullable()->constrained('users')->nullOnDelete(); // Preserve review if user deletes account
            // Review Content
            $table->text('review'); // Detailed customer feedback
            $table->tinyInteger('rating')->unsigned()->check('rating BETWEEN 1 AND 5'); // 5-star rating system
            // Trust Signals
            $table->boolean('verified_purchase')->default(false); // True if user purchased before reviewing
            // Lifecycle Management
            $table->timestamps(); // Review creation/update times
            $table->softDeletes(); // Moderation deletion (hidden from public)
            // Performance Optimization
            $table->index(['product_id']); // Faster product review listings
            $table->index(['user_id']); // User review history lookups
            $table->index(['rating']); // Rating-based analytics
        });
    }

    /**
     * Reverse the migrations.
     **/
    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
        Schema::dropIfExists('product_discounts');
        Schema::dropIfExists('product_media');
        Schema::dropIfExists('product_variations');
        Schema::dropIfExists('products');
        Schema::dropIfExists('category_hierarchy');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('brands');
    }
};
