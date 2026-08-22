<?php

use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\AdjustmentController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\CourierSettingController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\SeoSettingController;
use App\Http\Controllers\Admin\StoreSetupController;
use App\Http\Controllers\Admin\UnitController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('profile', [ProfileController::class, 'show']);
    Route::post('profile', [ProfileController::class, 'update']);

    Route::get('users', [UserController::class, 'index']);
    Route::get('users/{id}', [UserController::class, 'show']);
    Route::post('users', [UserController::class, 'store']);
    Route::post('users/{id}', [UserController::class, 'update']);
    Route::put('users/{id}', [UserController::class, 'update']);
    Route::delete('users/{id}', [UserController::class, 'destroy']);

    Route::apiResource('categories', CategoryController::class)->except('destroy');
    Route::get('clients/lookup', [ClientController::class, 'lookup']);
    Route::post('clients/{client}/status', [ClientController::class, 'updateStatus']);
    Route::apiResource('clients', ClientController::class)->except('destroy');
    Route::apiResource('brands', BrandController::class)->except('destroy');
    Route::apiResource('attributes', AttributeController::class)->except('destroy');
    Route::apiResource('units', UnitController::class)->except('destroy');
    Route::get('banner-types', [BannerController::class, 'types']);
    Route::post('banners/{banner}/status', [BannerController::class, 'updateStatus']);
    Route::apiResource('banners', BannerController::class)->except('destroy');
    Route::apiResource('accounts', AccountController::class)->except('destroy');
    Route::post('accounts/{account}/add-funds', [AccountController::class, 'addFunds']);
    Route::get('products', [ProductController::class, 'index']);
    Route::post('products', [ProductController::class, 'store']);
    Route::get('products/check/barcode', [ProductController::class, 'checkByBarcode']);
    Route::get('products/{product}', [ProductController::class, 'show']);
    Route::get('products/{product}/edit-payload', [ProductController::class, 'editPayload']);
    Route::post('products/{product}', [ProductController::class, 'update']);

    Route::post('adjustments', [AdjustmentController::class, 'store']);
    Route::get('adjustments', [AdjustmentController::class, 'index']);

    Route::get('products/{product}/stock-movements', [ProductController::class, 'stockMovements']);

    Route::get('orders', [OrderController::class, 'index']);
    Route::get('orders/lookup', [OrderController::class, 'lookup']);
    Route::get('orders/{order}', [OrderController::class, 'show']);
    Route::put('orders/{order}', [OrderController::class, 'update']);
    Route::post('orders', [OrderController::class, 'store']);
    Route::post('orders/{order}/status', [OrderController::class, 'updateStatus']);

    Route::get('courier-settings', [CourierSettingController::class, 'index']);
    Route::post('courier-settings', [CourierSettingController::class, 'updateOrCreate']);

    Route::get('seo-settings', [SeoSettingController::class, 'show']);
    Route::post('seo-settings', [SeoSettingController::class, 'updateOrCreate']);
    Route::get('seo-settings/404-logs', [SeoSettingController::class, 'logs']);
    Route::delete('seo-settings/404-logs', [SeoSettingController::class, 'clearLogs']);

    Route::get('business-profile', [StoreSetupController::class, 'show']);
    Route::post('business-profile', [StoreSetupController::class, 'update']);
    Route::delete('business-profile/logo', [StoreSetupController::class, 'deleteLogo']);

    Route::get('locations/divisions', [LocationController::class, 'divisions']);
    Route::get('locations/districts/{divisionId}', [LocationController::class, 'districts']);
    Route::get('locations/upazilas/{districtId}', [LocationController::class, 'upazilas']);
    Route::get('locations/areas/{upazilaId}', [LocationController::class, 'areas']);
});
