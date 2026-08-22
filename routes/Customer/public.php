<?php

use App\Http\Controllers\Admin\StoreSetupController;
use App\Http\Controllers\Customer\AttrController;
use App\Http\Controllers\Customer\AttributeController;
use App\Http\Controllers\Customer\AuthController;
use App\Http\Controllers\Customer\BannerController;
use App\Http\Controllers\Customer\BrandController;
use App\Http\Controllers\Customer\CategoryController;
use App\Http\Controllers\Customer\DistrictController;
use App\Http\Controllers\Customer\ProductController;
use App\Http\Controllers\Customer\SitemapController;
use App\Http\Controllers\Customer\VisitorController;
use App\Http\Controllers\Customer\WishlistController;
use Illuminate\Support\Facades\Route;

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::get('visitor/session', [VisitorController::class, 'session']);
Route::get('wishlist', [WishlistController::class, 'index']);
Route::post('wishlist/toggle', [WishlistController::class, 'toggle']);
Route::get('banners/{type}', [BannerController::class, 'getByType']);
Route::get('brands', [BrandController::class, 'index']);
Route::get('popular-brands', [BrandController::class, 'popularBrands']);
Route::get('popular-categories', [CategoryController::class, 'popularCategories']);
Route::get('popular-products', [ProductController::class, 'popularProducts']);
Route::get('best-selling-products', [ProductController::class, 'bestSellingProducts']);
Route::get('new-arrival-products', [ProductController::class, 'newArrivalProducts']);
Route::get('brand/{brand_id}/products', [ProductController::class, 'brandProducts']);
Route::get('category/{category_id}/products', [ProductController::class, 'categoryProducts']);
Route::get('product/{slug_url}', [ProductController::class, 'show']);
Route::get('business-profile', [StoreSetupController::class, 'show']);
Route::get('districts', [DistrictController::class, 'index']);
Route::get('products/filter', [ProductController::class, 'filterProducts']);
Route::get('sitemap-data', [SitemapController::class, 'index']);
Route::get('sitemap-data/products', [SitemapController::class, 'products']);
Route::get('sitemap-data/categories', [SitemapController::class, 'categories']);
Route::get('sitemap-data/brands', [SitemapController::class, 'brands']);

Route::get('attributes/{slug}/values', [AttributeController::class, 'getAttributeValues']);
