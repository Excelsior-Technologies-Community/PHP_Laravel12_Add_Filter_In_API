<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;


// ============================================================
// PRODUCT API ROUTES
// ============================================================

// Get products with filters, sorting and pagination
Route::get('/products', [ProductController::class, 'apiIndex']);

// API documentation
Route::get('/products/docs', [ProductController::class, 'apiDocs']);

// Live search
Route::get('/products/search', [ProductController::class, 'apiSearch']);

// Search history
Route::get(
    '/products/search/history',
    [ProductController::class, 'apiSearchHistory']
);

Route::post(
    '/products/search/history',
    [ProductController::class, 'apiSearchHistory']
);

// Create product
Route::post(
    '/products',
    [ProductController::class, 'apiStore']
);

// Show single product
Route::get(
    '/products/{id}',
    [ProductController::class, 'apiShow']
);

// Update product
Route::post(
    '/products/{id}',
    [ProductController::class, 'apiUpdate']
);

// Delete product
Route::delete(
    '/products/{id}',
    [ProductController::class, 'apiDelete']
);