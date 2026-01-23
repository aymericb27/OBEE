<?php

namespace App\Http\Controllers;

use App\Imports\UEImport;
use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use App\Services\CodeGeneratorService;
use App\Services\GenericListImportService;
use App\Services\GenericSingleImportService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\ErrorHandler\Debug;

class ImportController extends Controller
{
    public function __construct(private CodeGeneratorService $codeGen) {}

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
                $service = new GenericSingleImportService();
                $data = $service->extract($file, $config);

                switch ($config['type']) {
                    case 'UE':
                        $stored = $this->storeUE($data);
                        break;

                    case 'AAT':
                        $stored = $this->storeAAT($data);
                        break;

                    case 'AAV':
                        $stored = $this->storeAAV($data);
                        break;

                    case 'PRE':
                        $stored = $this->storePrerequis($data);
                        break;

                    case 'PRO':
                        $stored = $this->storeProgramme($data);
                        break;

                    default:
                        return response()->json(['message' => 'Type single non supporté'], 422);
                }

                return response()->json([
                    'status' => 'ok',
                    'mode'   => 'single',
                    'type'   => $config['type'],
                    'data'   => $data,
                    'stored' => $stored,
                ]);


            default:
                return response()->json([
                    'message' => "importMode inconnu: {$config['importMode']}"
                ], 422);
        }
    }




    public function storeUE($values)
    {
        $uid = Auth::user()->university_id;

        // --------------------------
        // VALIDATION GLOBALE
        // --------------------------
        $messages = [
            'required' => 'Le champ :attribute est obligatoire.',
            'string'   => 'Le champ :attribute doit être un texte.',
            'integer'  => 'Le champ :attribute doit être un nombre.',
            'array'    => 'Le champ :attribute doit être une liste.',
            'in'       => 'Le champ :attribute doit être une des valeurs suivantes : :values.',
            'max'      => 'Le champ :attribute est trop long (max :max caractères).',
            'min'      => 'Le champ :attribute doit être un nombre (min :min).',

            // Messages plus “métier” pour les champs imbriqués
            'aats.*.contribution.integer' => 'La contribution de l’AAT doit être un nombre (1, 2 ou 3).',
            'aats.*.contribution.in'      => 'La contribution de l’AAT doit valoir 1, 2 ou 3.',

            'aavs.*.contribution.integer' => 'La contribution de l’AAV doit être un nombre (1, 2 ou 3).',
            'aavs.*.contribution.in'      => 'La contribution de l’AAV doit valoir 1, 2 ou 3.',

            'programmes.*.semestre.integer' => 'Le semestre du programme doit être un nombre.',
            'programmes.*.semestre.min'     => 'Le semestre du programme doit être au minimum 1.',
        ];

        $attributes = [
            'ue.code' => 'Sigle de l’UE',
            'ue.name' => 'Libellé de l’UE',
            'ue.description' => 'Description de l’UE',
            'ue.ects' => 'ECTS de l’UE',

            'aats' => 'Liste des AAT',
            'aats.*.code' => 'Sigle de l’AAT',
            'aats.*.libelle' => 'Libellé de l’AAT',
            'aats.*.contribution' => 'Contribution de l’AAT',

            'aavs' => 'Liste des AAV',
            'aavs.*.code' => 'Sigle de l’AAV',
            'aavs.*.libelle' => 'Libellé de l’AAV',
            'aavs.*.contribution' => 'Contribution de l’AAV',

            'programmes' => 'Liste des programmes',
            'programmes.*.code' => 'Code du programme',
            'programmes.*.libelle' => 'Libellé du programme',
            'programmes.*.semestre' => 'Semestre du programme',

            'prerequis' => 'Liste des prérequis',
            'prerequis.*.code' => 'Sigle du prérequis',
            'prerequis.*.libelle' => 'Libellé du prérequis',
        ];

        $validator = Validator::make($values, [
            "ue.code" => "nullable|string|max:50",
            "ue.name" => "required|string|max:255",
            "ue.description" => "nullable|string",
            "ue.ects" => "required|integer|min:1|max:120",

            "aats" => "array",
            "aats.*.libelle" => "nullable|string|max:255",
            "aats.*.code" => "nullable|string|max:50",
            "aats.*.contribution" => "nullable|integer|in:1,2,3",

            "programmes" => "array",
            "programmes.*.code" => "nullable|string|max:50",
            "programmes.*.libelle" => "nullable|string|max:255",
            "programmes.*.semestre" => "nullable|integer|min:1",

            "aavs" => "array",
            "aavs.*.code" => "nullable|string|max:50",
            "aavs.*.libelle" => "nullable|string|max:255",
            "aavs.*.AATCode" => "nullable|string|max:50",
            "aavs.*.contribution" => "nullable|integer|in:1,2,3",

            "prerequis" => "array",
            "prerequis.*.code" => "nullable|string|max:50",
            "prerequis.*.libelle" => "nullable|string|max:255",
        ], $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        Log::debug($values);
        // --------------------------
        // 1) Création UE
        // --------------------------
        $ueCode = trim($values['ue']['code'] ?? '');
        $ueName = trim($values['ue']['name'] ?? '');
        $ueDesc = $values['ue']['description'] ?? null;
        $ueEcts = (int) ($values['ue']['ects'] ?? 0);

        $ue = UniteEnseignement::create([
            'name' => $ueName,
            'ects' => $ueEcts,
            'code' => $ueCode !== '' ? $ueCode : null,
            'description' => $ueDesc,
            'university_id' => $uid,
        ]);

        // -------------------------
        // 2) Liaison AAT ↔ UE (avec contribution)
        // -------------------------
        if (!empty($values['aats'])) {
            foreach ($values['aats'] as $aatData) {

                $code = trim($aatData['code'] ?? '');
                $name = trim($aatData['libelle'] ?? '');

                // Rien fourni
                if ($code === '' && $name === '') {
                    continue;
                }

                // Pas de code mais un libellé => générer
                if ($code === '' && $name !== '') {
                    $code = $this->codeGen->nextAAT();
                }

                // Rechercher (scopé université)
                $aat = AAT::where('code', $code)
                    ->where('university_id', $uid)
                    ->first();
                // Inexistant et pas de libellé => ignorer
                if (!$aat && $name === '') {
                    continue;
                }

                // Créer
                if (!$aat) {
                    $aat = AAT::create([
                        'code' => $code,
                        'name' => $name !== '' ? $name : null,
                        'university_id' => $uid,
                    ]);
                }

                // Mettre à jour name si vide en base et fourni
                if (($aat->name === null || trim((string)$aat->name) === '') && $name !== '') {
                    $aat->name = $name;
                    $aat->save();
                }

                $contribution = is_numeric($aatData['contribution'] ?? null)
                    ? (int) $aatData['contribution']
                    : null;

                // ⚠️ si ton pivot contient university_id, ajoute-le ici aussi
                $ue->aat()->syncWithoutDetaching([
                    $aat->id => [
                        'contribution' => $contribution,
                        'university_id' => $uid,
                    ]
                ]);
            }
        }

        // -------------------------
        // 3) Liaison Programmes ↔ UE (pivot semester)
        // -------------------------
        if (!empty($values['programmes'])) {
            foreach ($values['programmes'] as $proData) {
                $code = trim($proData['code'] ?? '');
                $name = trim($proData['libelle'] ?? '');
                $sem  = isset($proData['semestre']) && is_numeric($proData['semestre'])
                    ? (int) $proData['semestre']
                    : null;

                if ($code === '' && $name === '') {
                    continue;
                }

                if ($code === '' && $name !== '') {
                    $code = $this->codeGen->nextProgramme();
                }

                // Inexistant + pas de libellé => ignorer
                if ($code !== '') {
                    $pro = Programme::where('code', $code)
                        ->where('university_id', $uid)
                        ->first();
                } else {
                    $pro = null;
                }

                if (!$pro && $name === '') {
                    continue;
                }

                if (!$pro) {
                    $pro = Programme::create([
                        'code' => $code,
                        'name' => $name !== '' ? $name : null,
                        'university_id' => $uid,
                    ]);
                } else {
                    // update name si vide
                    if (($pro->name === null || trim((string)$pro->name) === '') && $name !== '') {
                        $pro->name = $name;
                        $pro->save();
                    }
                }

                // Attacher l’UE au programme avec semestre en pivot
                // ⚠️ si ton pivot contient university_id, ajoute-le ici aussi
                $pivot = [];
                if ($sem !== null) {
                    $pivot['semester'] = $sem;
                }
                $pivot['university_id'] = $uid;

                $ue->pro()->syncWithoutDetaching([
                    $pro->id => $pivot
                ]);
            }
        }

        // -------------------------
        // 4) Liaison AAV ↔ UE
        // -------------------------
        if (!empty($values['aavs'])) {
            foreach ($values['aavs'] as $aavData) {
                $code = trim($aavData['code'] ?? '');
                $name = trim($aavData['libelle'] ?? '');

                if ($code === '' && $name === '') {
                    continue;
                }

                if ($code === '' && $name !== '') {
                    $code = $this->codeGen->nextAAV();
                }

                $aav = AcquisApprentissageVise::where('code', $code)
                    ->where('university_id', $uid)
                    ->first();

                // Inexistant + pas de libellé => ignorer
                if (!$aav && $name === '') {
                    continue;
                }

                if (!$aav) {
                    $aav = AcquisApprentissageVise::create([
                        'code' => $code,
                        'name' => $name !== '' ? $name : null,
                        'university_id' => $uid,
                    ]);
                }

                if (($aav->name === null || trim((string)$aav->name) === '') && $name !== '') {
                    $aav->name = $name;
                    $aav->save();
                }

                // Attacher pivot (si pivot contient university_id)
                $ue->aavvise()->syncWithoutDetaching([
                    $aav->id => [
                        'university_id' => $uid,
                    ]
                ]);
            }
        }

        // -------------------------
        // 5) Liaison Prérequis ↔ UE
        // -------------------------
        if (!empty($values['prerequis'])) {
            foreach ($values['prerequis'] as $preData) {
                $code = trim($preData['code'] ?? '');
                $name = trim($preData['libelle'] ?? '');

                if ($code === '' && $name === '') {
                    continue;
                }

                if ($code === '' && $name !== '') {
                    $code = $this->codeGen->nextPrerequis();
                }

                $pre = AcquisApprentissageVise::where('code', $code)
                    ->where('university_id', $uid)
                    ->first();

                // Inexistant + pas de libellé => ignorer
                if (!$pre && $name === '') {
                    continue;
                }

                if (!$pre) {
                    $pre = AcquisApprentissageVise::create([
                        'code' => $code,
                        'name' => $name !== '' ? $name : null,
                        'university_id' => $uid,
                    ]);
                }

                if (($pre->name === null || trim((string)$pre->name) === '') && $name !== '') {
                    $pre->name = $name;
                    $pre->save();
                }

                $ue->prerequis()->syncWithoutDetaching([
                    $pre->id => [
                        'university_id' => $uid,
                    ]
                ]);
            }
        }

        return $ue;
    }
}
