<?php

use App\Http\Controllers\AcquisApprentissageTerminaux;
use App\Http\Controllers\AcquisApprentissageVise;
use App\Http\Controllers\Calendar;
use App\Http\Controllers\CalendarLesson;
use App\Http\Controllers\ErrorController;
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
Route::get('/ue/aavvise/get',[UniteEnseignement::class, 'getAAVvise'])->name('ue.get.aavvise');
Route::get('/ue/aavprerequis/get',[UniteEnseignement::class, 'getAAVprerequis'])->name('ue.get.aavprerequis');
Route::put('/ue/update', [UniteEnseignement::class, 'update'])->name('ue.update');


Route::post('/calendarStore', [CalendarLesson::class, 'store'])->name('calendar.store');
Route::get('/calendarLesson/index', [CalendarLesson::class, 'index'])->name('calendar.index');
Route::get('/CalendarLesson/get/detailed', [CalendarLesson::class, 'getDetailed'])->name('calendar.get.detailed');

Route::get('/aats/get', [AcquisApprentissageTerminaux::class, 'get']);
Route::get('/AATGetChildren', [treeController::class, 'getChildren']);
Route::get('/aat/get/detailed', [AcquisApprentissageTerminaux::class, 'getDetailed']);
Route::get('/aat/aavs/get', [AcquisApprentissageTerminaux::class, 'getAAVs']);

Route::get('/aav/get/detailed', [AcquisApprentissageVise::class, 'getDetailed']);
Route::get('/aav/aats/get', [AcquisApprentissageVise::class, 'getAATs']);
Route::get('/aavs/get', [AcquisApprentissageVise::class, 'get']);
Route::get('/aav/UEvise/get', [AcquisApprentissageVise::class, 'getUEvise']);
Route::get('/aav/UEPrerequis/get', [AcquisApprentissageVise::class, 'getUEprerequis']);

Route::get('/Error/UES', [ErrorController::class, 'getErrorUES']);
Route::get('/Error/UES/shedule', [ErrorController::class, 'getErrorUESShedule']);

Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');