<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\SellerProfile;
use App\Services\MarketplaceCheckout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceController extends Controller
{
    public function catalog(Request $request) { $products=Product::with(['seller.sellerProfile','category'])->where('status','approved')->whereHas('seller.sellerProfile',fn($q)=>$q->where('status','approved'))->when($request->filled('q'),fn($q)=>$q->where('name','like','%'.$request->string('q').'%'))->when($request->filled('category'),fn($q)=>$q->where('category_id',$request->integer('category')))->paginate(12);$categories=Category::orderBy('name')->get();return view('market.catalog',compact('products','categories')); }
    public function storeProfile(Request $request) { $data=$request->validate(['store_name'=>'required|string|max:100|unique:seller_profiles,store_name','description'=>'nullable|string|max:2000']); if($request->user()->sellerProfile()->exists()) return back()->withErrors(['store_name'=>'Seller profile already exists.']); DB::transaction(fn()=>SellerProfile::create(['user_id'=>$request->user()->id,...$data])); return back()->with('status','Seller application submitted for admin approval.'); }
    public function product(Request $request) { $data=$request->validate(['name'=>'required|string|max:160','description'=>'nullable|string|max:5000','price_minor'=>'required|integer|min:1|max:100000000','stock'=>'required|integer|min:0|max:1000000','category_id'=>'nullable|exists:categories,id']);$profile=$request->user()->sellerProfile()->where('status','approved')->firstOrFail();$profile->products()->create([...$data,'seller_id'=>$request->user()->id,'status'=>'pending']);return back()->with('status','Product submitted for moderation.'); }
    public function moderate(Request $request,int $product) { abort_unless($request->user()->role==='admin',403);$p=Product::findOrFail($product);$data=$request->validate(['status'=>'required|in:approved,rejected']);$p->update(['status'=>$data['status']]);return back(); }
    public function sellerApplicationModerate(Request $request,int $seller) { abort_unless($request->user()->role==='admin',403);$p=SellerProfile::findOrFail($seller);$data=$request->validate(['status'=>'required|in:approved,rejected']);$p->update($data);$p->user->forceFill(['role'=>$data['status']==='approved'?'seller':'customer'])->save();return back(); }
    public function admin(Request $request) { abort_unless($request->user()->role==='admin',403);$sellers=SellerProfile::with('user')->latest()->get();$products=Product::with('seller')->where('status','pending')->latest()->get();return view('market.admin',compact('sellers','products')); }
    public function sellerDashboard(Request $request) { abort_unless($request->user()->sellerProfile?->status==='approved',403);$products=$request->user()->products()->get();$orders=$request->user()->sellerOrders()->with('items','order')->latest()->get();$sales=$orders->sum('subtotal_minor');$commission=$orders->sum('commission_minor');return view('market.seller',compact('products','orders','sales','commission')); }
    public function addToCart(Request $request,int $product) { $data=$request->validate(['quantity'=>'required|integer|min:1|max:100']);$p=Product::whereKey($product)->where('status','approved')->whereHas('seller.sellerProfile',fn($q)=>$q->where('status','approved'))->firstOrFail();$cart=$request->session()->get('cart',[]);$cart[$p->id]=min($p->stock,($cart[$p->id]??0)+$data['quantity']);$request->session()->put('cart',$cart);return back()->with('status','Added to cart.'); }
    public function cart(Request $request) { $products=Product::whereIn('id',array_keys($request->session()->get('cart',[])))->where('status','approved')->get();$items=$products->map(fn($p)=>['product'=>$p,'quantity'=>$request->session()->get('cart')[$p->id]]);return view('market.cart',compact('items')); }
    public function checkout(Request $request,MarketplaceCheckout $checkout) { $cart=$request->session()->get('cart',[]);$order=$checkout->checkout($request->user()->id,$cart);$request->session()->forget('cart');return redirect()->route('market.orders')->with('status','Order placed. Payment remains unpaid; no payment was collected.'); }
    public function orders(Request $request) { $orders=$request->user()->orders()->with('sellerOrders.items')->latest()->get();return view('market.orders',compact('orders')); }
    public function sellerOrders(Request $request) { abort_unless($request->user()->sellerProfile?->status==='approved',403);$orders=$request->user()->sellerOrders()->with('items','order')->latest()->get();return view('market.seller-orders',compact('orders')); }
}
