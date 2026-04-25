<?php

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

Route::middleware(['anti.bot'])->group(function () {
    Route::get('/products', function () {
        return response()->json([
            ['id' => 1, 'name' => 'Laravel Book', 'price' => 500],
            ['id' => 2, 'name' => 'Redis Guide', 'price' => 300],
        ]);
    });
});
