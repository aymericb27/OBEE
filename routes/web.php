<?php

use App\Http\Controllers\UE;
use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    return view('welcome');
});

Route::post('/UEStore', [UE::class, 'store'])->name('UE.store');
Route::get('/UEGet', [UE::class, 'get'])->name('UE.get');
