<?php

use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

// Resource route автоматически создаёт:
// GET /api/products → index
// POST /api/products → store
// GET /api/products/{product} → show
// PUT/PATCH /api/products/{product} → update
// DELETE /api/products/{product} → destroy

Route::apiResource('products', ProductController::class);

// Если нужно отключить какие-то методы:
// Route::apiResource('products', ProductController::class)->except(['destroy']);
