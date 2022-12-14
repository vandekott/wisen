<?php

use App\Models\Telegram\Userbot;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route('filament.auth.login');
});

Route::get('/getUserInfo/{nickname}', function ($nickname) {
    return response()->json(
        Userbot::find(2)->getApi()->getInfo($nickname),
        200,
        ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
        JSON_UNESCAPED_UNICODE
    );
});

Route::get('/getChatInfo/{peer}', function ($peer) {
    return response()->json(
        Userbot::all()->random()->getApi()->getChatInfo($peer),
        200,
        ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'],
        JSON_UNESCAPED_UNICODE
    );
});
