<?php

// use App\Http\Controllers\PiAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

use App\Http\Controllers\Api\PiAuthController;

Route::post('/pi/auth', [PiAuthController::class, 'authenticate']);
Route::middleware('auth:sanctum')->get('/me', [PiAuthController::class, 'me']);



// مسارات Pi
// Route::get('/pi/redirect', [PiAuthController::class, 'redirectToPi']);
// Route::get('/pi/callback', [PiAuthController::class, 'handlePiCallback']);

// مسارات أخرى
// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::middleware('auth:sanctum')->post('/create-payment', [PiAuthController::class, 'createPayment']);
