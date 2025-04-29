_______________________________________________________________________________________________________________________________
------------------------------------------------------------------------------------------------
-- Orders table: Stores order details for each transaction
------------------------------------------------------------------------------------------------
CREATE TABLE orders (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each order
    user_id CHAR(26) NOT NULL,  -- References the user who placed the order
    total_price DECIMAL(10, 2) NOT NULL,  -- Total price of the order
    status ENUM('pending', 'paid', 'shipped', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending',  -- Replaces FK with enum
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when the order was created
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when the order was last updated
    deleted_at TIMESTAMP NULL,  -- Soft delete timestamp for logical deletion

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,  -- Cascade delete when user is deleted

    INDEX (user_id),
    INDEX (status)
);

------------------------------------------------------------------------------------------------
-- Order items table: Stores individual items within an order
------------------------------------------------------------------------------------------------
CREATE TABLE order_items (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each order item
    order_id BIGINT NOT NULL,  -- References the related order
    product_id BIGINT NOT NULL,  -- References the purchased product
    quantity UNSIGNED INT NOT NULL,  -- Number of items purchased
    price DECIMAL(10, 2) NOT NULL,  -- Price per unit of the product
    subtotal DECIMAL(10, 2) NOT NULL,  -- Total price for this item (quantity * price)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,  -- Timestamp when item was added to order
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,  -- Timestamp when item was last updated
    
    -- Foreign keys
    CONSTRAINT fk_order_id FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,  -- Cascade delete when order is deleted
    CONSTRAINT fk_product_id FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,  -- Cascade delete when product is deleted

    -- Indexes
    INDEX idx_order_id (order_id),  -- Optimizes order-based lookups
    INDEX idx_product_id (product_id)  -- Optimizes product-based lookups
);
------------------------------------------------------------------------------------------------
-- Order payments table: Stores payment details for each order
------------------------------------------------------------------------------------------------
CREATE TABLE order_payments (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- Unique identifier for each payment record
    order_id BIGINT UNSIGNED NOT NULL,  -- References the associated order
    payment_status ENUM('unpaid', 'paid', 'pending', 'failed', 'refunded', 'cancelled') DEFAULT 'unpaid',  -- Status of the payment
    payment_method ENUM('credit_card', 'paypal', 'cash_on_delivery', 'bank_transfer') NOT NULL,  -- Payment method used
    transaction_id VARCHAR(255) NULL,  -- Nullable transaction ID for payment tracking

    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,  -- Cascade delete when order is deleted
    INDEX (order_id)  -- Optimizes lookups based on order ID
);
------------------------------------------------------------------------------------------------
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    
    invoice_number VARCHAR(100) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL DEFAULT CURRENT_DATE,
    due_date DATE NULL,

    subtotal DECIMAL(10, 2) NOT NULL,
    tax_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,

    status ENUM('unpaid', 'paid', 'refunded', 'cancelled') NOT NULL DEFAULT 'unpaid',
    currency_code VARCHAR(10) NOT NULL DEFAULT 'USD',

    pdf_path VARCHAR(255) NULL,
    notes TEXT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_invoice_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_currency FOREIGN KEY (currency_code) REFERENCES currencies(code),

    INDEX idx_invoice_order (order_id),
    INDEX idx_invoice_number (invoice_number)
);

------------------------------------------------------------------------------------------------

CREATE TABLE invoice_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    sku VARCHAR(100) NULL,

    quantity INT UNSIGNED NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    tax_rate DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
    discount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10, 2) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_invoice_item_invoice FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    CONSTRAINT fk_invoice_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,

    INDEX idx_invoice_item_invoice_id (invoice_id),
    INDEX idx_invoice_item_product_id (product_id)
);


------------------------------------------------------------------------------------------------
# Order Creation Workflow in an E-Commerce Website

## 1. Customer Initiates Purchase
- User adds items to shopping cart
- User proceeds to checkout (may be logged in or as guest)

## 2. Checkout Process
### a. Shipping Information
- Collect delivery address
- Select shipping method (with costs and estimated delivery times)
- Option to save address for future purchases

### b. Payment Information
- Select payment method (credit card, PayPal, etc.)
- Enter payment details
- Apply promo codes/discounts
- Display order summary with subtotal, taxes, shipping, and total

## 3. Order Validation
- Verify product availability (inventory check)
- Validate payment information
- Check for fraud indicators
- Confirm shipping feasibility

## 4. Order Confirmation
- Process payment (authorization or capture)
- Generate unique order number
- Create order record in database with:
  - Customer information
  - Product details
  - Payment details
  - Shipping information
  - Order status (initially "Pending" or "Processing")

## 5. Notification
- Send order confirmation email to customer with:
  - Order number
  - Itemized list
  - Total amount
  - Expected delivery date
  - Tracking information (if available immediately)
- Internal notification to fulfillment team

## 6. Order Processing
- Inventory allocation (reduce stock levels)
- Generate pick list for warehouse
- Print shipping labels
- Update order status to "Processing"

## 7. Fulfillment
- Items picked from inventory
- Packaged for shipment
- Handed to shipping carrier
- Tracking number assigned
- Order status updated to "Shipped"
- Shipping confirmation sent to customer

## 8. Post-Order Updates
- Tracking updates (if integrated with carrier)
- Delivery confirmation
- Option for customer to initiate returns/exchanges
- Request for customer review/feedback

## Additional Considerations:
- **Guest checkout flow** (without account creation)
- **Saved payment methods** for returning customers
- **Order status page** accessible to customer
- **Cancellation window** (if order hasn't shipped)
- **Fraud detection systems** integration
- **Tax calculation** based on location
- **Multi-channel inventory sync** (if applicable)

_______________________________________________________________________________________________________________________________