<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\ClientController;
use App\Http\Controllers\API\MessageController;
use App\Models\User;

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
Route::apiResource('message', MessageController::class);


Route::get('discussions/{clientId}', [MessageController::class, 'index']);
Route::get('discussions/{clientId}/{interId}', [MessageController::class, 'messagesBetweenClients']);
Route::put('/updateReadStatus/{clientId}/{interlocuteurId}', [MessageController::class, 'updateReadStatus']);



Route::get('commentaire/{userId}', [UserController::class, 'list_commentaire']);
Route::post('enreg_commentaire', [ClientController::class, 'enreg_commentaire']);

Route::get('notation/{userId}', [UserController::class, 'list_notation']);
Route::post('enreg_notation', [UserController::class, 'enreg_notation']);

Route::get('detail_user', [UserController::class, 'show']);

Route::get('domaine', [UserController::class, 'domaine']);

Route::get('search', [UserController::class, 'search']);

Route::get('global_search', [UserController::class, 'global_search']);
Route::get('index_global', [UserController::class, 'index_global']);

Route::get('search_domaine', [UserController::class, 'domaine_search']);

Route::post('code_client', [ClientController::class, 'store_verification']);

Route::post('code_user', [UserController::class, 'store_verification']);
Route::post('client_user', [UserController::class, 'client_user']);

Route::post('/login', [UserController::class, 'login']);




