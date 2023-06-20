<?php

use App\Http\Controllers\DataController;
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
Route::get('/data/getColumns', [DataController::class, 'getColumns']);
Route::get('/data/getUser', [DataController::class, 'getUser']);
Route::post('/data/addUser', [DataController::class, 'addUser']);
Route::post('/data/deleteUser', [DataController::class, 'deleteUser']);
Route::post('/data/createPDF', [DataController::class, 'createPDF']);