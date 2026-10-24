<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
 public function up(): void {
  Schema::table('users', function(Blueprint $t){$t->string('role')->default('customer');});
  Schema::create('categories',function(Blueprint $t){$t->id();$t->string('name')->unique();$t->timestamps();});
  Schema::create('seller_profiles',function(Blueprint $t){$t->id();$t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();$t->string('store_name')->unique();$t->text('description')->nullable();$t->string('status')->default('pending');$t->timestamps();});
  Schema::create('products',function(Blueprint $t){$t->id();$t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();$t->foreignId('category_id')->nullable()->constrained()->nullOnDelete();$t->string('name');$t->text('description')->nullable();$t->unsignedBigInteger('price_minor');$t->unsignedInteger('stock')->default(0);$t->string('status')->default('pending');$t->timestamps();$t->index(['status','category_id']);});
  Schema::create('orders',function(Blueprint $t){$t->id();$t->foreignId('customer_id')->constrained('users')->cascadeOnDelete();$t->string('status')->default('placed');$t->unsignedBigInteger('subtotal_minor');$t->unsignedBigInteger('commission_minor');$t->string('payment_status')->default('unpaid');$t->timestamps();});
  Schema::create('seller_orders',function(Blueprint $t){$t->id();$t->foreignId('order_id')->constrained()->cascadeOnDelete();$t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();$t->unsignedBigInteger('subtotal_minor');$t->unsignedBigInteger('commission_minor');$t->string('status')->default('placed');$t->timestamps();$t->unique(['order_id','seller_id']);});
  Schema::create('order_items',function(Blueprint $t){$t->id();$t->foreignId('seller_order_id')->constrained()->cascadeOnDelete();$t->foreignId('product_id')->nullable()->constrained()->nullOnDelete();$t->string('product_name');$t->unsignedBigInteger('unit_price_minor');$t->unsignedInteger('quantity');$t->unsignedBigInteger('line_total_minor');$t->timestamps();});
  Schema::create('marketplace_settings',function(Blueprint $t){$t->id();$t->unsignedTinyInteger('commission_percent')->default(10);$t->timestamps();});
 }
 public function down(): void {Schema::dropIfExists('marketplace_settings');Schema::dropIfExists('order_items');Schema::dropIfExists('seller_orders');Schema::dropIfExists('orders');Schema::dropIfExists('products');Schema::dropIfExists('seller_profiles');Schema::dropIfExists('categories');Schema::table('users',fn(Blueprint $t)=>$t->dropColumn('role'));}
};
