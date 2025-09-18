<?php

use App\Http\Controllers\ElementConstitutif;
use App\Http\Controllers\UniteEnseignement;
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

Route::post('/UEStore', [UniteEnseignement::class, 'store'])->name('UE.store');
Route::get('/UEGet', [UniteEnseignement::class, 'get'])->name('UE.get');

Route::get('/ECGet', [ElementConstitutif::class, 'get'])->name('EC.get');
Route::post('/ECStore', [ElementConstitutif::class, 'store'])->name('EC.store');
