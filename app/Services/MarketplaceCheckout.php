<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MarketplaceCheckout
{
    public function checkout(int $customerId, array $cart): Order
    {
        if ($cart === []) throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        return DB::transaction(function () use ($customerId, $cart) {
            $lines = [];
            foreach ($cart as $id => $quantity) {
                if (filter_var($id, FILTER_VALIDATE_INT) === false || $id < 1 || filter_var($quantity, FILTER_VALIDATE_INT) === false || $quantity < 1 || $quantity > 100) throw ValidationException::withMessages(['cart' => 'Cart contents are invalid.']);
                $product = Product::whereKey($id)->where('status', 'approved')->whereHas('seller.sellerProfile', fn ($q) => $q->where('status', 'approved'))->lockForUpdate()->first();
                if (!$product || $product->stock < $quantity) throw ValidationException::withMessages(['cart' => 'A product is unavailable or stock is insufficient.']);
                $product->decrement('stock', $quantity);
                $lineTotal = $product->price_minor * $quantity;
                $lines[$product->seller_id][] = [$product, $quantity, $lineTotal];
            }
            $settings = DB::table('marketplace_settings')->first();
            $rate = $settings?->commission_percent ?? 10;
            $subtotal = array_sum(array_map(fn ($sellerLines) => array_sum(array_column($sellerLines, 2)), $lines));
            $commission = intdiv($subtotal * $rate + 50, 100);
            $order = Order::create(['customer_id' => $customerId, 'subtotal_minor' => $subtotal, 'commission_minor' => $commission, 'payment_status' => 'unpaid']);
            foreach ($lines as $sellerId => $sellerLines) {
                $sellerSubtotal = array_sum(array_column($sellerLines, 2));
                $sellerCommission = intdiv($sellerSubtotal * $rate + 50, 100);
                $sellerOrder = SellerOrder::create(['order_id' => $order->id, 'seller_id' => $sellerId, 'subtotal_minor' => $sellerSubtotal, 'commission_minor' => $sellerCommission]);
                foreach ($sellerLines as [$product, $quantity, $lineTotal]) $sellerOrder->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'unit_price_minor' => $product->price_minor, 'quantity' => $quantity, 'line_total_minor' => $lineTotal]);
            }
            return $order;
        });
    }
}
