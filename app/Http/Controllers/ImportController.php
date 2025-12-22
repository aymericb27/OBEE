<?php

namespace App\Http\Controllers;

use App\Imports\UEImport;
use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use App\Services\GenericListImportService;
use App\Services\GenericSingleImportService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file'   => 'required|file',
            'config' => 'required|string',
        ]);

        $file   = $request->file('file');
        $config = json_decode($request->config, true);

        if (!isset($config['importMode'])) {
            return response()->json([
                'message' => "config.importMode manquant",
            ], 422);
        }

        switch ($config['importMode']) {

            case "list":
                $service = new GenericListImportService();
                $rows = $service->extract($file, $config);
                $stored = [];

                foreach ($rows as $data) {
                    switch ($config['type']) {

                        case "AAT":
                            $stored[] = AAT::updateOrCreate(
                                ['code' => $data['code']],
                                ['name' => $data['name'] ?? null]
                            );
                            break;

                        case "AAV":
                            $stored[] = AcquisApprentissageVise::updateOrCreate(
                                ['code' => $data['code']],
                                ['name' => $data['name'] ?? null]
                            );
                            break;
                    }
                }
                return response()->json([
                    'status' => 'ok',
                    'mode'   => "list",
                    'type'   => $config['type'] ?? null,
                    'count'  => count($rows),
                    'data'   => $rows,
                ]);

            case "single":
                // TODO next step
                // extraction générique
                $service = new GenericSingleImportService();
                $data = $service->extract($file, $config);
                $this->storeUE($data);
                return response()->json([
                    'status' => 'ok',
                    'mode'   => "single",
                    'data' => $data,
                    'message' => "Mode single pas encore implémenté"
                ]);

            default:
                return response()->json([
                    'message' => "importMode inconnu: {$config['importMode']}"
                ], 422);
        }
    }

    /*     public function import(Request $request)
    {
        $request->validate([
            'file'   => 'required|file',
            'config' => 'required|string',
        ]);
        $config = json_decode($request->config, true);
        $service =
        $import = new UEImport();
        //Excel::import($import, $request->file('file'));
        //return $this->storeUE($import->parsedData);
        return response()->json([
            'message' => 'Import réussi',
            'data' => $import->parsedData
        ]);
    } */

    public function importAAT(Request $request)
    {
        $request->validate([
            'startRow' => 'required|integer|min:1',
            'endRow' => 'required|integer|min:1',
            'columns' => 'required|array',
            'columns.code' => 'required|string',
            'columns.name' => 'required|string',
            'file' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new \App\Imports\AATImport(
            startRow: $request->startRow,
            endRow: $request->endRow,
            columnMap: $request->columns
        );

        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => 'Import OK',
            'data' => $import->parsedData,
        ]);
    }





    public function storeUE($values)
    {
        // --------------------------
        // VALIDATION GLOBALE
        // --------------------------
        $validator = Validator::make($values, [
            "ue.code" => "nullable|string|max:50",
            "ue.name"    => "required|string|max:255",
            "ue.description" => "nullable|string",
            "ue.ects"     => "required|integer|min:1|max:120",

            "aats"               => "array",
            "aats.*.libelle"        => "nullable|string",
            "aats.*.code" => "nullable|string",
            "aats.*.contribution" => "nullable|integer|in:1,2,3",

            "programmes"                  => "array",
            "programmes.*.code"    => "nullable|string",
            "programmes.*.libelle"        => "nullable|string",
            "programmes.*.semestre"       => "nullable|integer|min:1",

            "aavs"                         => "array",
            "aavs.*.code"           => "nullable|string",
            "aavs.*.libelle"               => "nullable|string",
            "aavs.*.AATCode"        => "nullable|string",
            "aavs.*.contribution"          => "nullable|integer|in:1,2,3",

            "prerequis"                    => "array",
            "prerequis.*.code"      => "nullable|string",
            "prerequis.*.libelle"          => "nullable|string",
        ], [
            // Messages personnalisés (clairs)
            "required" => "Le champ :attribute est obligatoire.",
            "integer"  => "Le champ :attribute doit être un nombre.",
            "in"       => "Le champ :attribute doit être une des valeurs suivantes : :values",
            "max"      => "Le champ :attribute est trop long.",
            "min"      => "Le champ :attribute est trop court."
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $ue = UniteEnseignement::create([
            'name' => $values['ue']['name'],
            'ects' => $values['ue']['ects'],
            'code' => $values['ue']['code'],
            'description' => $values['ue']['description'],
        ]);

        // -------------------------
        // 2) Liaison AAT de l’UE
        // -------------------------
        if (!empty($values['aats'])) {
            foreach ($values['aats'] as $aatData) {

                $code = trim($aatData['code'] ?? '');
                $name = trim($aatData['libelle'] ?? '');

                if ($code === '') continue; // sécurité minimale

                // 1) Rechercher AAT existant
                $aat = AAT::where('code', $code)->first();

                // 2) Si pas trouvé et PAS de libellé => ignorer totalement
                if (!$aat && $name === '') {
                    continue;
                }

                // 3) Si pas trouvé mais libellé existe => créer
                if (!$aat) {
                    $aat = AAT::create([
                        'code' => $code,
                        'name' => $name
                    ]);
                }

                // 4) Si trouvé et name vide dans DB => updater si envoyé
                if ($aat->name === null && $name !== '') {
                    $aat->name = $name;
                    $aat->save();
                }

                // 5) Attacher pivot contribution
                $contribution = is_numeric($aatData['contribution'] ?? null)
                    ? (int) $aatData['contribution']
                    : null;

                $ue->aat()->syncWithoutDetaching([
                    $aat->id => ['contribution' => $contribution]
                ]);
            }
        }



        // -------------------------
        // 2) Liaison PROGRAMME de l’UE
        // -------------------------
        if (!empty($values['programme'])) {

            foreach ($values['programmes'] as $proData) {

                // créer ou récupérer le programme
                $pro = Programme::firstOrCreate([
                    'code' => $proData['code'],
                ], ['name' => $proData['libelle'], 'semestre' => $proData['semestre']]);

                // attacher le programme à l’UE
                $ue->pro()->syncWithoutDetaching([
                    $pro->id => [
                        'semester' => $proData['semestre']
                    ]
                ]);
            }
        }

        // -------------------------
        // Liaison AAV ↔ UE avec AAT associé
        // -------------------------
        if (!empty($values['aavs'])) {

            foreach ($values['aavs'] as $aavData) {

                /** ---------------------------
                 * 1) Gestion AAT associé
                 * --------------------------*/
                $aatId = null;
                $aatCode = trim($aavData['AATCode'] ?? '');
                $aatName = trim($aavData['AATName'] ?? '');
                $contribAAT = $aavData['contribution'] ?? null;

                if ($aatCode !== '') {
                    $aat = AAT::where('code', $aatCode)->first();

                    if (!$aat && $aatName !== '') {
                        $aat = AAT::create([
                            'code' => $aatCode,
                            'name' => $aatName
                        ]);
                    }

                    if ($aat && !$aat->name && $aatName !== '') {
                        $aat->name = $aatName;
                        $aat->save();
                    }

                    if (isset($aat)) {
                        $aatId = $aat->id;
                    }
                }

                /** ---------------------------
                 * 2) Gestion de l’AAV
                 * --------------------------*/
                $code = trim($aavData['code'] ?? '');
                $name = trim($aavData['libelle'] ?? '');

                // 🟡 Cas 0 : aucun code et aucun nom → ignorer
                if ($code === '' && $name === '') {
                    continue;
                }

                // 🟢 Cas 1 : pas de code mais un nom ⇒ créer AAV avec code auto
                if ($code === '' && $name !== '') {
                    // Récupère le dernier code existant
                    $lastAAV = AcquisApprentissageVise::where('code', 'LIKE', 'AAV%')
                        ->orderBy('code', 'desc')
                        ->first();

                    if ($lastAAV) {
                        // extrait le numéro : PRO012 → 12
                        $lastNumber = intval(substr($lastAAV->code, 3));
                        $newNumber = $lastNumber + 1;
                    } else {
                        $newNumber = 1; // premier code
                    }

                    // format UE001 / UE024 / UE300…
                    $code = 'AAV' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
                }

                // 🔎 recherche existant
                $aav = AcquisApprentissageVise::where('code', $code)->first();

                // 🟠 Cas 2 : inexistant & code présent mais nom vide ⇒ ignorer
                if (!$aav && $name === '') {
                    continue;
                }

                // 🟢 Cas 3 : création
                if (!$aav) {
                    $aav = AcquisApprentissageVise::create([
                        'code'         => $code,
                        'name'         => $name,
                        'fk_AAT'       => $aatId,
                        'contribution' => $contribAAT
                    ]);
                }

                // 🟣 Mise à jour name si vide
                if ($aav->name === null && $name !== '') {
                    $aav->name = $name;
                }

                // 🟣 Mise à jour fk_AAT si vide
                if (!$aav->fk_AAT && $aatId) {
                    $aav->fk_AAT = $aatId;
                }

                // 🟣 Mise à jour contribution
                if ($contribAAT !== null) {
                    $aav->contribution = $contribAAT;
                }

                $aav->save();

                // 🟢 Association à l’UE
                $ue->aavvise()->syncWithoutDetaching([$aav->id]);
            }
        }


        // -------------------------
        // 2) Liaison PROGRAMME de l’UE
        // -------------------------
        if (!empty($values['prerequis'])) {

            foreach ($values['prerequis'] as $preData) {

                // créer ou récupérer le prérequis
                $pre = AcquisApprentissageVise::firstOrCreate([
                    'code' => $preData['code'],
                ], ['name' => $preData['libelle']]);

                // attacher le prérequis à l’UE
                $ue->prerequis()->syncWithoutDetaching([$pre->id]);
            }
        }
        return $ue;
    }
}
