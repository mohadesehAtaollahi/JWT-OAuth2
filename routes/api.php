<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
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
