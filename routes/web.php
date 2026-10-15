<?php

use App\Http\Controllers\Store\CategoryController;
use App\Http\Controllers\Store\ComplaintController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\PageController;
use App\Http\Controllers\Store\ProductController;
use Illuminate\Support\Facades\Route;

/*
 * Storefront paths follow the ones of the store on hade.tiendada.com so old links keep
 * their shape: /products, /product/…, /category/…, /information/….
 */
Route::get('/', HomeController::class)->name('home');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/category/{category:slug}', [CategoryController::class, 'show'])->name('categories.show');
Route::get('/information/complaints', [ComplaintController::class, 'create'])->name('complaints.create');
Route::post('/information/complaints', [ComplaintController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('complaints.store');
Route::get('/information/{page:slug}', [PageController::class, 'show'])->name('pages.show');
