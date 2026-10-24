<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Models\User;
use App\Services\MarketplaceCheckout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function seller(string $name): User
    {
        $user = User::factory()->create(['role' => 'seller']);
        SellerProfile::create(['user_id' => $user->id, 'store_name' => $name, 'status' => 'approved']);
        return $user;
    }

    public function test_mixed_seller_checkout_splits_orders_calculates_fee_and_decrements_stock(): void
    {
        $sellerA = $this->seller('Alpha'); $sellerB = $this->seller('Beta'); $buyer = User::factory()->create();
        $a = Product::create(['seller_id'=>$sellerA->id,'name'=>'A','price_minor'=>1050,'stock'=>5,'status'=>'approved']);
        $b = Product::create(['seller_id'=>$sellerB->id,'name'=>'B','price_minor'=>3000,'stock'=>2,'status'=>'approved']);
        $order = app(MarketplaceCheckout::class)->checkout($buyer->id, [$a->id=>2,$b->id=>1]);
        $this->assertSame(5100, $order->subtotal_minor);
        $this->assertSame(510, $order->commission_minor);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(2, $order->sellerOrders()->count());
        $this->assertSame(2100, $order->sellerOrders()->where('seller_id',$sellerA->id)->value('subtotal_minor'));
        $this->assertSame(210, $order->sellerOrders()->where('seller_id',$sellerA->id)->value('commission_minor'));
        $this->assertSame(3000, $order->sellerOrders()->where('seller_id',$sellerB->id)->value('subtotal_minor'));
        $this->assertSame(3, $a->fresh()->stock); $this->assertSame(1, $b->fresh()->stock);
    }

    public function test_checkout_failure_rolls_back_prior_stock_decrement_and_order_writes(): void
    {
        $seller = $this->seller('Stock'); $buyer = User::factory()->create();
        $ok = Product::create(['seller_id'=>$seller->id,'name'=>'Available','price_minor'=>100,'stock'=>3,'status'=>'approved']);
        $scarce = Product::create(['seller_id'=>$seller->id,'name'=>'Scarce','price_minor'=>200,'stock'=>1,'status'=>'approved']);
        try { app(MarketplaceCheckout::class)->checkout($buyer->id, [$ok->id=>2,$scarce->id=>2]); $this->fail('Expected stock validation failure.'); }
        catch (ValidationException) {}
        $this->assertSame(3, $ok->fresh()->stock); $this->assertSame(1, $scarce->fresh()->stock);
        $this->assertSame(0, Order::count());
    }

    public function test_seller_order_endpoint_never_returns_another_sellers_orders(): void
    {
        $owner=$this->seller('Owner');$outsider=$this->seller('Outsider');$buyer=User::factory()->create();
        $order=Order::create(['customer_id'=>$buyer->id,'status'=>'placed','subtotal_minor'=>500,'commission_minor'=>50,'payment_status'=>'unpaid']);
        $order->sellerOrders()->create(['seller_id'=>$owner->id,'subtotal_minor'=>500,'commission_minor'=>50,'status'=>'placed']);
        $this->actingAs($outsider)->get('/seller/orders')->assertOk()->assertSee('No seller orders.')->assertDontSee('500 minor units');
    }

    public function test_customer_order_history_is_private_and_catalog_excludes_pending_products(): void
    {
        $seller=$this->seller('Public shop');$buyer=User::factory()->create();$other=User::factory()->create();
        $approved=Product::create(['seller_id'=>$seller->id,'name'=>'Visible widget','price_minor'=>150,'stock'=>2,'status'=>'approved']);
        Product::create(['seller_id'=>$seller->id,'name'=>'Pending secret','price_minor'=>150,'stock'=>2,'status'=>'pending']);
        $order=Order::create(['customer_id'=>$buyer->id,'status'=>'placed','subtotal_minor'=>150,'commission_minor'=>15,'payment_status'=>'unpaid']);
        $order->sellerOrders()->create(['seller_id'=>$seller->id,'subtotal_minor'=>150,'commission_minor'=>15,'status'=>'placed']);
        $this->actingAs($buyer)->get('/')->assertOk()->assertSee('Visible widget')->assertDontSee('Pending secret');
        $this->actingAs($buyer)->get('/orders')->assertOk()->assertSee('Order #'.$order->id);
        $this->actingAs($other)->get('/orders')->assertOk()->assertSee('No orders yet.')->assertDontSee('Order #'.$order->id);
        $this->assertNotNull($approved->fresh());
    }

    public function test_suspended_seller_products_cannot_be_purchased_from_a_stale_cart(): void
    {
        $seller=$this->seller('Suspended shop');$buyer=User::factory()->create();
        $product=Product::create(['seller_id'=>$seller->id,'name'=>'Stale listing','price_minor'=>100,'stock'=>2,'status'=>'approved']);
        $seller->sellerProfile->update(['status'=>'rejected']);
        try { app(MarketplaceCheckout::class)->checkout($buyer->id,[$product->id=>1]); $this->fail('Expected unavailable listing to abort checkout.'); }
        catch (ValidationException) {}
        $this->assertSame(2,$product->fresh()->stock);
        $this->assertSame(0,Order::count());
    }

    public function test_admin_can_approve_seller_but_registration_never_grants_roles(): void
    {
        $admin=User::factory()->create();$admin->forceFill(['role'=>'admin'])->save();
        $applicant=User::factory()->create();
        $profile=SellerProfile::create(['user_id'=>$applicant->id,'store_name'=>'Applicant','status'=>'pending']);
        $this->actingAs($admin)->patch("/admin/sellers/{$profile->id}",['status'=>'approved'])->assertRedirect();
        $this->assertSame('seller',$applicant->fresh()->role);
        $this->post('/logout');
        $this->post('/register',['name'=>'Self Role','email'=>'selfrole@example.com','password'=>'Password!123','password_confirmation'=>'Password!123','role'=>'admin'])->assertRedirect();
        $this->assertSame('customer',User::where('email','selfrole@example.com')->firstOrFail()->role);
    }

    public function test_regular_customer_cannot_moderate_products(): void
    {
        $seller=$this->seller('Moderation');$customer=User::factory()->create();
        $product=Product::create(['seller_id'=>$seller->id,'name'=>'Pending','price_minor'=>100,'stock'=>1,'status'=>'pending']);
        $this->actingAs($customer)->patch("/admin/products/{$product->id}", ['status'=>'approved'])->assertForbidden();
        $this->assertSame('pending',$product->fresh()->status);
    }
}
