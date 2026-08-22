<?php

use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\ClientAddressController;
use App\Http\Controllers\Customer\OrderController;
use App\Http\Controllers\Customer\ProfileController;
use App\Http\Controllers\Customer\WishlistController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::post('wishlist/merge', [WishlistController::class, 'merge']);
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'updateProfile']);
        Route::put('/password', [ProfileController::class, 'updatePassword']);
    });

    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index']);
        Route::post('/', [CartController::class, 'store']);
        Route::put('/{id}', [CartController::class, 'update']);
        Route::delete('/{id}', [CartController::class, 'destroy']);
        Route::delete('/', [CartController::class, 'clear']);
    });

    Route::prefix('addresses')->group(function () {
        Route::get('/', [ClientAddressController::class, 'index']);
        Route::post('/', [ClientAddressController::class, 'store']);
        Route::put('/{id}', [ClientAddressController::class, 'update']);
        Route::delete('/{id}', [ClientAddressController::class, 'destroy']);
        Route::put('/{id}/set-default', [ClientAddressController::class, 'setDefault']);
    });

    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index']);
        Route::post('/', [OrderController::class, 'store']);
        Route::get('/{id}', [OrderController::class, 'show']);
    });
    Route::post('orders', [OrderController::class, 'store']);
});
