<?php

namespace App\Http\Controllers\Api\Public\Orders;

use Exception;
use App\Models\Cart;
use Illuminate\Support\Str;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use App\Models\Orders\OrderItem;
use Illuminate\Http\JsonResponse;
use App\Models\Orders\VendorOrder;
use Illuminate\Support\Facades\DB;
use App\Http\Responses\ApiResponse;
use App\Models\Orders\OrderPayment;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Services\Orders\OrderService;
use App\Http\Requests\PaginateRequest;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\ValidateColumnAndConditionRequest;

class OrderController extends Controller
{
    /**
     * Constructor to inject the OrderService dependency.
     *
     * @param OrderService $orderService The service responsible for order-related operations.
     */
    public function __construct(protected OrderService $orderService) {}

    public function getCheckout()
    {
        $user = Auth::user();
        $cartItems = $user->cartItems;

        $subtotal = $cartItems->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // $shippingMethods = ShippingMethod::active()->get();
        $addresses = $user ? $user->addresses : collect();

        return ApiResponse::success([
            'cart_items' => $cartItems,
            'subtotal' => $subtotal,
            // 'shippingMethods'=>$shippingMethods,
            // 'addresses'=>$addresses,
        ]);
    }

    /**
     * Place a new order from the user's cart
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        $data = $request->validate([
            'payment_method' => 'required|in:credit_card,paypal,stripe,bank_transfer,cash',
            'currency_code' => 'required|exists:currencies,code',
        ]);

        $cartItems = Cart::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Cart is empty.'], 400);
        }

        DB::beginTransaction();

        try {
            // Group cart items by vendor
            $grouped = $cartItems->groupBy('product.vendor_id');

            // Create the main order
            // $order = Order::create([
            //     'user_id'      => $user->id,
            //     'order_number' => Order::generateOrderNumber(),//strtoupper(Str::random(10)),
            //     'subtotal'     => $cartItems->sum(fn($item) => $item->price * $item->quantity),
            //     'tax'          => 0, // You can calculate tax logic if needed
            //     'total_price'  => $cartItems->sum(fn($item) => $item->price * $item->quantity),
            //     'status'       => 'pending',
            // ]);

            // test
            $order = Order::find(1);
            return ApiResponse::success($order);
            
                foreach ($grouped as $vendorId => $items) {
                    // Create vendor order
                    $vendorOrder = VendorOrder::create([
                        'order_id'            => $order->id,
                        'vendor_id'           => $vendorId,
                        'vendor_order_number' => VendorOrder::generateOrderNumber(),//strtoupper(Str::random(10)),
                        'subtotal'            => $items->sum(fn($item) => $item->price * $item->quantity),
                        'tax'                 => 0,
                        'total_price'         => $items->sum(fn($item) => $item->price * $item->quantity),
                        'status'              => 'pending',
                    ]);

                    // Create order items
                    foreach ($items as $item) {
                        OrderItem::create([
                            'vendor_order_id' => $vendorOrder->id,
                            'product_id'      => $item->product_id,
                            'variation_id'    => $item->variation_id,
                            'quantity'        => $item->quantity,
                            'price'           => $item->price,
                        ]);
                    }
                }

                // Create main payment record
                $orderPayment = OrderPayment::create([
                    'order_id'      => $order->id,
                    'method'        => $request->payment_method,
                    'status'        => 'pending',
                    'amount'        => $order->total_price,
                    'currency_code' => $request->currency_code,
                ]);

                // Clear cart
                Cart::where('user_id', $user->id)->delete();

                DB::commit();

                return response()->json([
                    'message' => 'Order placed successfully!',
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to place order.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an order status (admin or system)
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,refunded',
        ]);

        $order = Order::findOrFail($orderId);
        $order->update([
            'status' => $request->status,
        ]);

        return response()->json(['message' => 'Order status updated.']);
    }

    /**
     * Show order details
     */
    public function show($orderId)
    {
        $order = Order::with([
            'vendorOrders.orderItems.product',
            'vendorOrders.orderItems.variation',
            'payments'
        ])->findOrFail($orderId);

        return response()->json($order);
    }

    /**
     * List all orders for logged-in user
     */
    public function index()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->with('vendorOrders')
            ->latest()
            ->get();

        return response()->json($orders);
    }



    // public function store(CheckoutRequest $request)
    // {
    //     return DB::transaction(function () use ($request) {
    //         // 1. Validate cart and inventory
    //         $cartItems = Cart::getUserCart();
    //         $this->validateCart($cartItems);

    //         // 2. Calculate totals
    //         $totals = $this->calculateTotals($cartItems, $request->shipping_method_id);

    //         // 3. Create order
    //         $order = $this->createOrder($request, $totals);

    //         // 4. Add order items
    //         $this->addOrderItems($order, $cartItems);

    //         // 5. Process payment
    //         $payment = $this->processPayment($order, $request);

    //         // 6. Clear cart and reduce inventory
    //         $this->completeCheckout($cartItems);

    //         // 7. Send notifications
    //         $this->sendNotifications($order);

    //         return redirect()->route('order.confirmation', $order->order_number);
    //     });
    // }

    // protected function validateCart($cartItems)
    // {
    //     if ($cartItems->isEmpty()) {
    //         abort(redirect()->route('cart.show')->with('error', 'Your cart is empty'));
    //     }

    //     foreach ($cartItems as $item) {
    //         $available = $item->variation_id
    //             ? $item->variation->stock_quantity
    //             : $item->product->stock_quantity;

    //         if ($available < $item->quantity) {
    //             abort(redirect()->route('cart.show')->with('error', "Not enough stock for {$item->product->name}"));
    //         }
    //     }
    // }

    // protected function calculateTotals($cartItems, $shippingMethodId)
    // {
    //     $subtotal = $cartItems->sum(function ($item) {
    //         return $item->price * $item->quantity;
    //     });

    //     $shippingMethod = ShippingMethod::findOrFail($shippingMethodId);
    //     $taxRate = config('sales.tax_rate', 0.1); // 10% default
    //     $tax = $subtotal * $taxRate;

    //     return [
    //         'subtotal' => $subtotal,
    //         'tax' => $tax,
    //         'shipping_cost' => $shippingMethod->cost,
    //         'total' => $subtotal + $tax + $shippingMethod->cost
    //     ];
    // }

    // protected function createOrder($request, $totals)
    // {
    //     $orderData = array_merge($totals, [
    //         'order_number' => Order::generateOrderNumber(),
    //         'user_id' => Auth::id(),
    //         'guest_email' => !Auth::check() ? $request->email : null,
    //         'shipping_address_id' => $request->shipping_address_id,
    //         'billing_address_id' => $request->billing_address_id ?? $request->shipping_address_id,
    //         'shipping_method_id' => $request->shipping_method_id,
    //         'notes' => $request->notes,
    //     ]);

    //     return Order::create($orderData);
    // }

    // protected function addOrderItems($order, $cartItems)
    // {
    //     foreach ($cartItems as $item) {
    //         $order->items()->create([
    //             'product_id' => $item->product_id,
    //             'variation_id' => $item->variation_id,
    //             'quantity' => $item->quantity,
    //             'price' => $item->price,
    //             'options' => $item->options
    //         ]);
    //     }
    // }

    // protected function processPayment($order, $request)
    // {
    //     $paymentService = app(PaymentService::class);
    //     return $paymentService->process(
    //         $order,
    //         $request->payment_method,
    //         $request->payment_token
    //     );
    // }

    // protected function completeCheckout($cartItems)
    // {
    //     // Reduce inventory
    //     foreach ($cartItems as $item) {
    //         if ($item->variation_id) {
    //             $item->variation->decrement('stock_quantity', $item->quantity);
    //         } else {
    //             $item->product->decrement('stock_quantity', $item->quantity);
    //         }
    //     }

    //     // Clear cart
    //     Cart::clearUserCart();
    // }

    // protected function sendNotifications($order)
    // {
    //     // Send email to customer
    //     event(new OrderCreated($order));

    //     // Notify admin/staff
    //     Notification::send(User::admin()->get(), new NewOrderNotification($order));
    // }
}