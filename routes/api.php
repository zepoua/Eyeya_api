<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClientController;

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

Route::apiResource('client', ClientController::class);
Route::apiResource('user', UserController::class);


Route::get('commentaire/{userId}', [UserController::class, 'list_commentaire']);
Route::post('enreg_commentaire', [ClientController::class, 'enreg_commentaire']);

Route::get('notation/{userId}', [UserController::class, 'list_notation']);
Route::post('enreg_notation', [ClientController::class, 'enreg_notation']);

Route::get('message', [ClientController::class, 'list_message']);
Route::post('enreg_message', [ClientController::class, 'enreg_message']);

Route::get('domaine', [UserController::class, 'domaine']);

Route::get('search', [UserController::class, 'search']);

Route::post('/login', [UserController::class, 'login']);




