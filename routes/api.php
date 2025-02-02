<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PageController;
use App\Http\Controllers\API\MediaController;
use App\Http\Controllers\API\TeamMemberController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => 'auth'], function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::middleware('jwt.auth')->group(function () {
    Route::get('media', [MediaController::class, 'index']);
    Route::get('media/{id}', [MediaController::class, 'show']);
    Route::post('media', [MediaController::class, 'store']);
    Route::delete('media/{id}', [MediaController::class, 'destroy']);
    Route::apiResource('pages', PageController::class);
    Route::apiResource('team-members', TeamMemberController::class);
});