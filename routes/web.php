<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketplaceController::class, 'catalog'])->name('market.catalog');
Route::get('/dashboard', fn () => redirect()->route('market.catalog'))->middleware('auth')->name('dashboard');
Route::get('/cart', [MarketplaceController::class, 'cart'])->name('market.cart');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/seller/apply', [MarketplaceController::class, 'storeProfile'])->name('market.seller.apply');
    Route::get('/admin', [MarketplaceController::class, 'admin'])->name('market.admin');
    Route::post('/seller/products', [MarketplaceController::class, 'product'])->name('market.products.store');
    Route::get('/seller', [MarketplaceController::class, 'sellerDashboard'])->name('market.seller');
    Route::get('/seller/orders', [MarketplaceController::class, 'sellerOrders'])->name('market.seller.orders');
    Route::post('/checkout', [MarketplaceController::class, 'checkout'])->name('market.checkout');
    Route::get('/orders', [MarketplaceController::class, 'orders'])->name('market.orders');
    Route::patch('/admin/products/{product}', [MarketplaceController::class, 'moderate'])->whereNumber('product')->name('market.admin.products.moderate');
    Route::patch('/admin/sellers/{seller}', [MarketplaceController::class, 'sellerApplicationModerate'])->whereNumber('seller')->name('market.admin.sellers.moderate');
    Route::post('/products/{product}/cart', [MarketplaceController::class, 'addToCart'])->whereNumber('product')->name('market.cart.add');
});

require __DIR__.'/auth.php';
