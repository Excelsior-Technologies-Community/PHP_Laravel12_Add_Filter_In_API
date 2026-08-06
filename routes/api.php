<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;

// 🔹 PRODUCT API ROUTES
Route::get('/products', [ProductController::class, 'apiIndex']);
Route::get('/products/docs', [ProductController::class, 'apiDocs']);
Route::get('/products/search', [ProductController::class, 'apiSearch']);
Route::get('/products/search/history', [ProductController::class, 'apiSearchHistory']);
Route::post('/products/search/history', [ProductController::class, 'apiSearchHistory']);
Route::post('/products', [ProductController::class, 'apiStore']);
Route::get('/products/{id}', [ProductController::class, 'apiShow']);
Route::post('/products/{id}', [ProductController::class, 'apiUpdate']);
Route::delete('/products/{id}', [ProductController::class, 'apiDelete']);
