<?php

use App\Http\Controllers\Api\PiAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group which will apply rate limiting.
|
*/

// ===== مسارات المصادقة (بدون CSRF token) =====
Route::post('/pi/auth', [PiAuthController::class, 'authenticate'])
    ->middleware('throttle:10,1')
    ->name('pi.auth');

// ===== مسارات محمية بـ Sanctum =====
Route::middleware('auth:sanctum')->group(function () {
    // معلومات المستخدم الحالي
    Route::get('/me', [PiAuthController::class, 'me'])
        ->name('user.me');

    // تسجيل الخروج
    Route::post('/logout', [PiAuthController::class, 'logout'])
        ->name('logout');
});

// ===== معالجة الـ 404 =====
Route::fallback(function () {
    return response()->json([
        'error' => 'not_found',
        'message' => 'المسار غير موجود'
    ], 404);
});
