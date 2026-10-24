<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerOrder extends Model
{
    protected $fillable = ['order_id', 'seller_id', 'subtotal_minor', 'commission_minor', 'status'];
    public function order() { return $this->belongsTo(Order::class); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function items() { return $this->hasMany(OrderItem::class); }
}
