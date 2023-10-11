<?php

use App\Http\Controllers\BroadcastController;
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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::apiResource('broadcasts', BroadcastController::class);
Route::post('broadcasts/{broadcast}/targets', [BroadcastController::class, 'addTargetToBroadcast']);
Route::post('broadcasts/{broadcast}/execute', [BroadcastController::class, 'executeBroadcast']);
