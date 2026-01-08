<?php

use App\Http\Controllers\AcquisApprentissageTerminaux;
use App\Http\Controllers\AcquisApprentissageVise;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\UniteEnseignement;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;


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

//** Unité d'enseignement **//
Route::get('/ues/get', [UniteEnseignement::class, 'get'])->name('UE.get');
Route::get('/UEGet/detailed', [UniteEnseignement::class, 'getDetailed'])->name('UE.get.detailed');
Route::get('/ue/ecs/get', [UniteEnseignement::class, 'getECs'])->name('ue.get.ecs');
Route::get('/ue/aat/get', [UniteEnseignement::class, 'getAATs'])->name('ue.get.aat');
Route::get('ue/pro/get', [UniteEnseignement::class, 'getPro'])->name('ue.pro.get');
Route::get('/ue/aavvise/get', [UniteEnseignement::class, 'getAAVvise'])->name('ue.get.aavvise');
Route::get('/ue/aavvise/get/onlyParent', [UniteEnseignement::class, 'getAAVviseOnlyParent'])->name('ue.get.aavvise');
Route::get('/ue/aavprerequis/get', [UniteEnseignement::class, 'getAAVprerequis'])->name('ue.get.aavprerequis');
Route::put('/ue/update', [UniteEnseignement::class, 'update'])->name('ue.update');
Route::post('/ue/store', [UniteEnseignement::class, 'store'])->name('ue.update');
Route::delete('ue/delete', [UniteEnseignement::class, 'delete']);
Route::post('/ues/add/EC', [UniteEnseignement::class, 'addEC']);
//** Programme **//
Route::get('/pro/get', [ProgrammeController::class, 'get'])->name('pro.get');
Route::delete('/pro/delete', [ProgrammeController::class, 'delete'])->name('pro.delete');
Route::get('/pro/get/detailed', [ProgrammeController::class, 'getDetailed'])->name('pro.get.detailed');
Route::get('/programme/get/tree', [ProgrammeController::class, 'getTree'])->name('pro.get.tree');
Route::get('/pro/ue/get', [ProgrammeController::class, 'getUE']);
Route::post('/programme/create', [ProgrammeController::class, 'store'])->name('pro.store');
Route::put('/programme/{id}', [ProgrammeController::class, 'update']);
Route::post('/programme/add-semester', [ProgrammeController::class, 'addSemestre']);
Route::post('/programme/ues/add', [ProgrammeController::class, 'addUEs'])->name('pro.store');
Route::post('/programme/update', [ProgrammeController::class, 'update']);

//** Acquis d'apprentissage terminaux **//
Route::get('/aat/get', [AcquisApprentissageTerminaux::class, 'get']);
Route::post('/aat/store', [AcquisApprentissageTerminaux::class, 'store']);
Route::get('/aat/get/tree', [AcquisApprentissageTerminaux::class, 'getTree']);
Route::get('/aat/get/detailed', [AcquisApprentissageTerminaux::class, 'getDetailed']);
Route::get('/aat/aavs/get', [AcquisApprentissageTerminaux::class, 'getAAVs']);
Route::delete('/aat/delete', [AcquisApprentissageTerminaux::class, 'delete']);
Route::post('/aat/update', [AcquisApprentissageTerminaux::class, 'update']);

//** Acquis d'apprentissage visé **//
Route::get('/aav/get/detailed', [AcquisApprentissageVise::class, 'getDetailed']);
Route::get('/aav/aats/get', [AcquisApprentissageVise::class, 'getAATs']);
Route::get('/aav/pre/get', [AcquisApprentissageVise::class, 'getOnlyPrerequis']);
Route::get('/aav/get', [AcquisApprentissageVise::class, 'get']);
Route::delete('/aav/delete', [AcquisApprentissageVise::class, 'delete']);
Route::post('/aav/store', [AcquisApprentissageVise::class, 'store']);
Route::post('/aav/update', [AcquisApprentissageVise::class, 'update']);
Route::get('/aav/prerequis/get', [AcquisApprentissageVise::class, 'getOnlyPrerequis']);
Route::get('/aav/UEvise/get', [AcquisApprentissageVise::class, 'getUEvise']);
Route::get('/aav/UEPrerequis/get', [AcquisApprentissageVise::class, 'getUEprerequis']);

//** Gestion des erreurs **//
Route::get('/Error/UE', [ErrorController::class, 'getErrorUE']);
Route::get('/Error/UES', [ErrorController::class, 'getErrorUES']);
Route::get('/Error/UES/shedule', [ErrorController::class, 'getErrorUESShedule']);
Route::get('/Error/pro/ects/number', [ErrorController::class, 'getErrorProEctsNumber']);

//** Exportation **//
Route::get('/export/get/{type}', [ExportController::class, 'export']);
Route::get('/export/ue/{id}', [ExportController::class, 'exportUE']);

//** importation **//
Route::post('/import/post', [ImportController::class, 'import']);
Route::post('/import/aat', [ImportController::class, 'importAAT'])->name('import.aat');
Route::post('/import/generic', [ImportController::class, 'import']);

Route::get('/download/model_import', function () {
    return response()->download(
        storage_path('app/public/files/model_import.xlsx')
    );
});
Route::get('/{any}', function () {
    return view('welcome');
})->where('any', '.*');
