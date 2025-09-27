<?php

use App\Http\Controllers\AcquisApprentissageTerminaux;
use App\Http\Controllers\Calendar;
use App\Http\Controllers\CalendarLesson;
use App\Http\Controllers\ElementConstitutif;
use App\Http\Controllers\treeController;
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
Route::get('/ues/get', [UniteEnseignement::class, 'get'])->name('UE.get');
Route::get('/UEGet/detailed', [UniteEnseignement::class, 'getDetailed'])->name('UE.get.detailed');
Route::get('/ue/ecs/get', [UniteEnseignement::class, 'getECs'])->name('ue.get.ecs');
Route::get('/ue/aavs/get',[UniteEnseignement::class, 'getAAVs'])->name('ue.get.aavs');

Route::get('/ecs/get', [ElementConstitutif::class, 'get'])->name('EC.get');
Route::post('/ECStore', [ElementConstitutif::class, 'store'])->name('EC.store');
Route::get('/ECGet/detailed', [ElementConstitutif::class, 'getDetailed'])->name('ec.get.detailed');

Route::post('/calendarStore', [CalendarLesson::class, 'store'])->name('calendar.store');
Route::get('/calendarLesson/index', [CalendarLesson::class, 'index'])->name('calendar.index');
Route::get('/CalendarLesson/get/detailed', [CalendarLesson::class, 'getDetailed'])->name('calendar.get.detailed');

Route::get('/aats/get', [AcquisApprentissageTerminaux::class, 'get']);
Route::get('/AATGetChildren', [treeController::class, 'getChildren']);
Route::get('/AATGet/detailed', [AcquisApprentissageTerminaux::class, 'getDetailed']);

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');