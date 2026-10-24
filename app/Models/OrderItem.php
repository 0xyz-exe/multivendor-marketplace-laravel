<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = ['seller_order_id', 'product_id', 'product_name', 'unit_price_minor', 'quantity', 'line_total_minor'];
    public function sellerOrder() { return $this->belongsTo(SellerOrder::class); }
}
