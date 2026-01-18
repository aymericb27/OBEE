<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UniteEnseignement;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\AcquisApprentissageTerminaux;
use App\Http\Controllers\AcquisApprentissageVise;
use App\Http\Controllers\ErrorController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\AdminUserController;

require __DIR__ . '/auth.php'; // ✅ contient GET /login, GET /register, POST /login etc.

/**
 * ✅ SPA Vue (welcome/template) uniquement si connecté
 */
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/users/pending', [AdminUserController::class, 'pending']);
    Route::post('/admin/users/{user}/approve', [AdminUserController::class, 'approve']);

    // optionnel
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);
});
Route::middleware(['auth', 'approved'])->group(function () {
    // routes de l'app

    // page d'entrée de l'app (charge Vue)
    Route::get('/', fn() => view('welcome'));

    // ✅ important : fallback pour toutes les routes Vue (history mode)

    // --- tes endpoints protégés ---
    Route::get('/ues/get', [UniteEnseignement::class, 'get'])->name('UE.get');
    Route::get('/UEGet/detailed', [UniteEnseignement::class, 'getDetailed'])->name('UE.get.detailed');
    Route::get('/ue/ecs/get', [UniteEnseignement::class, 'getChildren'])->name('ue.get.ecs');
    Route::get('/ue/aat/get', [UniteEnseignement::class, 'getAATs'])->name('ue.get.aat');
    Route::get('/ue/pro/get', [UniteEnseignement::class, 'getPro'])->name('ue.pro.get');
    Route::get('/ue/parent/get', [UniteEnseignement::class, 'getParent'])->name('ue.get.parent');

    Route::get('/ue/aavvise/get', [UniteEnseignement::class, 'getAAVvise'])->name('ue.get.aavvise');
    Route::get('/ue/aavvise/get/onlyParent', [UniteEnseignement::class, 'getAAVviseOnlyParent'])->name('ue.get.aavvise.onlyParent');

    Route::get('/ue/aavprerequis/get', [UniteEnseignement::class, 'getAAVprerequis'])->name('ue.get.aavprerequis');
    Route::put('/ue/update', [UniteEnseignement::class, 'update'])->name('ue.update');
    Route::post('/ue/store', [UniteEnseignement::class, 'store'])->name('ue.store');
    Route::delete('/ue/delete', [UniteEnseignement::class, 'delete']);
    Route::post('/ues/add/EC', [UniteEnseignement::class, 'addEC']);

    // Programme
    Route::get('/pro/get', [ProgrammeController::class, 'get'])->name('pro.get');
    Route::delete('/pro/delete', [ProgrammeController::class, 'delete'])->name('pro.delete');
    Route::get('/pro/get/detailed', [ProgrammeController::class, 'getDetailed'])->name('pro.get.detailed');
    Route::get('/programme/get/tree', [ProgrammeController::class, 'getTree'])->name('pro.get.tree');
    Route::get('/pro/ue/get', [ProgrammeController::class, 'getUE']);
    Route::post('/programme/create', [ProgrammeController::class, 'store'])->name('pro.store');
    Route::post('/programme/update', [ProgrammeController::class, 'update'])->name('pro.update');

    // AAT
    Route::get('/aat/get', [AcquisApprentissageTerminaux::class, 'get']);
    Route::post('/aat/store', [AcquisApprentissageTerminaux::class, 'store']);
    Route::get('/aat/get/tree', [AcquisApprentissageTerminaux::class, 'getTree']);
    Route::get('/aat/get/detailed', [AcquisApprentissageTerminaux::class, 'getDetailed']);
    Route::get('/aat/aavs/get', [AcquisApprentissageTerminaux::class, 'getAAVs']);
    Route::delete('/aat/delete', [AcquisApprentissageTerminaux::class, 'delete']);
    Route::post('/aat/update', [AcquisApprentissageTerminaux::class, 'update']);

    // AAV
    Route::get('/aav/get/detailed', [AcquisApprentissageVise::class, 'getDetailed']);
    Route::get('/aav/aats/get', [AcquisApprentissageVise::class, 'getAATs']);
    Route::get('/aav/get', [AcquisApprentissageVise::class, 'get']);
    Route::delete('/aav/delete', [AcquisApprentissageVise::class, 'delete']);
    Route::post('/aav/store', [AcquisApprentissageVise::class, 'store']);
    Route::post('/aav/update', [AcquisApprentissageVise::class, 'update']);
    Route::get('/aav/prerequis/get', [AcquisApprentissageVise::class, 'getOnlyPrerequis']);
    Route::get('/aav/UEvise/get', [AcquisApprentissageVise::class, 'getUEvise']);
    Route::get('/aav/UEPrerequis/get', [AcquisApprentissageVise::class, 'getUEprerequis']);

    // Errors
    Route::get('/Error/UE', [ErrorController::class, 'getErrorUE']);
    Route::get('/Error/UES', [ErrorController::class, 'getErrorUES']);
    Route::get('/Error/UES/shedule', [ErrorController::class, 'getErrorUESShedule']);
    Route::get('/Error/pro/ects/number', [ErrorController::class, 'getErrorProEctsNumber']);

    // Export
    Route::get('/export/get/{type}', [ExportController::class, 'export']);
    Route::get('/export/ue/{id}', [ExportController::class, 'exportUE']);
    Route::get('/export/aat/{id}', [ExportController::class, 'exportAAT']);
    Route::get('/export/aav/{id}', [ExportController::class, 'exportAAV']);
    Route::get('/export/pro/{id}', [ExportController::class, 'exportPRO']);

    // Import
    Route::post('/import/post', [ImportController::class, 'import']);
    Route::post('/import/aat', [ImportController::class, 'importAAT'])->name('import.aat');
    Route::post('/import/generic', [ImportController::class, 'import']);
    Route::get('/{any}', fn() => view('welcome'))->where('any', '.*');
});
