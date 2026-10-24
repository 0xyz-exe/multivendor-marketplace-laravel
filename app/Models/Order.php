<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['customer_id', 'status', 'subtotal_minor', 'commission_minor', 'payment_status'];
    public function sellerOrders() { return $this->hasMany(SellerOrder::class); }
    public function customer() { return $this->belongsTo(User::class, 'customer_id'); }
}
