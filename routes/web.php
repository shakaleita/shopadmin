<?php
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;
Route::get('/',[ShopController::class,'index'])->name('shop.index');
Route::post('/products',[ShopController::class,'product'])->name('products.store');
Route::post('/orders',[ShopController::class,'order'])->name('orders.store');
Route::post('/orders/{order}/status',[ShopController::class,'status'])->name('orders.status');

Route::get('/api/insights',[ShopController::class,'insights'])->name('shop-insights');

Route::get('/api/export/shop-orders.csv',[ShopController::class,'export'])->name('shop-orders-export');
