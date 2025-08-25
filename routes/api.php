<?php
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;

use App\Http\Middleware\RefreshMiddleware;
use App\Http\Middleware\MeMiddleware;

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'], function ($router) {
    Route::post('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api')->name('logout');
    Route::post('/refresh', [AuthController::class, 'refresh'])->middleware([RefreshMiddleware::class])->name('refresh');
    Route::post('/me', [AuthController::class, 'me'])->middleware(['auth:api', MeMiddleware::class])->name('me');
});
// Products routes
Route::prefix('products')->middleware('api')->group(function () {
    Route::get('/', [ProductController::class, 'index']);
    Route::get('/search', [ProductController::class, 'search']);
    Route::post('/add', [ProductController::class, 'store'])->middleware('auth:api');
    Route::get('/categories', [CategoryController::class, 'index'])->name('products.categories');
    Route::get('/category-list', [CategoryController::class, 'list']);
    Route::get('/category/{slug}', [CategoryController::class, 'productsByCategory'])
        ->name('products.byCategory');
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update'])->middleware('auth:api');
    Route::delete('/{id}', [ProductController::class, 'destroy'])->middleware('auth:api');

});
