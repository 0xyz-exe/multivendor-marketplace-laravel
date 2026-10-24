<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['seller_id', 'category_id', 'name', 'description', 'price_minor', 'stock', 'status'];
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
    public function category() { return $this->belongsTo(Category::class); }
}
