<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CaptchaController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// 驗證 API (排除在反爬蟲 Middleware 之外以免無限迴圈)
Route::post('/captcha/verify', [CaptchaController::class, 'verify'])->name('captcha.verify');

// 需要保護的 Web 路由
Route::middleware(['anti.bot'])->group(function () {
    Route::get('/products', function () {
        return view('products');
    });
});
