<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SellerProfile extends Model
{
    protected $fillable = ['user_id', 'store_name', 'description', 'status'];
    public function user() { return $this->belongsTo(User::class); }
    public function products() { return $this->hasMany(Product::class, 'seller_id'); }
}
