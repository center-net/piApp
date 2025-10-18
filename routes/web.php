<?php

use App\Http\Controllers\PiAuthController as WebPiAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group which will apply session state,
| encryption for your cookies and session protection from CSRF attacks.
|
*/

// ===== الصفحة الرئيسية =====
Route::get('/', function () {
    return view('welcome-new');
})->name('home');

// ===== مسارات المصادقة =====
Route::get('/login', function () {
    return view('auth.login');
})->middleware('guest')->name('login');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

// ===== صفحة مصادقة Pi (في WebView) =====
Route::view('/pi-login', 'pi_auth')
    ->name('pi.login');

// ===== مسارات OAuth2 مع Pi =====
Route::middleware('guest')->group(function () {
    Route::get('/pi/redirect', [WebPiAuthController::class, 'redirectToPi'])
        ->name('pi.redirect');
});

Route::get('/pi/callback', [WebPiAuthController::class, 'handlePiCallback'])
    ->name('pi.callback');

// ===== تسجيل الخروج =====
Route::post('/logout', function () {
    auth()->logout();
    return redirect('/');
})->middleware('auth')->name('logout');

// ===== معالجة الـ 404 =====
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});
