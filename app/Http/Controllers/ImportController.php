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
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\ErrorHandler\Debug;

class ImportController extends Controller
{
    public function __construct(private CodeGeneratorService $codeGen) {}

    public function import(Request $request)
    {
        $request->validate([
            'file'   => 'required|file|mimes:xlsx,xls|max:20480',
            'config' => 'required|string',
        ]);

        $file   = $request->file('file');
        $config = json_decode($request->config, true);

        if (!$file || !$file->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => "Le fichier uploadé est invalide.",
                'errors' => [
                    'file' => ["Code d'erreur upload: " . ($file?->getError() ?? 'unknown')],
                ],
            ], 422);
        }

        if (!isset($config['importMode'])) {
            return response()->json([
                'message' => "config.importMode manquant",
            ], 422);
        }

        switch ($config['importMode']) {


            case "list":
                $service = new GenericListImportService();
                try {
                    $rows = $service->extract($file, $config);
                } catch (ValidationException $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Le fichier Excel est invalide ou illisible.",
                        'errors' => $e->errors(),
                    ], 422);
                } catch (\Throwable $e) {
                    Log::error('Import list: lecture du fichier échouée', [
                        'user_id' => Auth::id(),
                        'filename' => $file->getClientOriginalName(),
                        'message' => $e->getMessage(),
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Erreur lors de la lecture du fichier Excel.",
                    ], 422);
                }

                $stored = [];
                $errors = [];
                $warnings = [];

                foreach ($rows as $index => $data) {
                    try {
                        switch ($config['type']) {
                            case "AAT":
                                $payload = [
                                    'type' => 'AAT',
                                    'main' => [
                                        'code' => $data['code'] ?? null,
                                        'name' => $data['name'] ?? null,
                                        'description' => $data['description'] ?? null,
                                    ],
                                    'links' => [],
                                ];
                                [$storedItem, $itemWarnings] = $this->normalizeStoreResult($this->storeAAT($payload));
                                $stored[] = $storedItem;
                                foreach ($itemWarnings as $warningMessage) {
                                    $warnings[] = [
                                        'rowIndex' => $index,
                                        'row' => $data,
                                        'type' => 'warning',
                                        'message' => $warningMessage,
                                    ];
                                }
                                break;

                            case "UE":
                                $payload = [
                                    'type' => 'UE',
                                    'main' => [
                                        'code' => $data['code'] ?? null,
                                        'name' => $data['name'] ?? null,
                                        'description' => $data['description'] ?? null,
                                        'ects' => isset($data['ects']) ? (int) $data['ects'] : null,
                                    ],
                                    'links' => [],
                                ];
                                [$storedItem, $itemWarnings] = $this->normalizeStoreResult($this->storeUE($payload));
                                $stored[] = $storedItem;
                                foreach ($itemWarnings as $warningMessage) {
                                    $warnings[] = [
                                        'rowIndex' => $index,
                                        'row' => $data,
                                        'type' => 'warning',
                                        'message' => $warningMessage,
                                    ];
                                }
                                break;

                            default:
                                // on force une validation-style error
                                throw ValidationException::withMessages([
                                    'type' => ["Type list non supporté: " . ($config['type'] ?? 'null')]
                                ]);
                        }
                    } catch (ValidationException $e) {
                        $errors[] = [
                            'rowIndex' => $index,
                            'row' => $data,
                            'type' => 'validation',
                            'errors' => $e->errors(), // détails par champ
                        ];
                    } catch (QueryException $e) {
                        // utile si tu as une contrainte unique, duplicate key, etc.
                        $errors[] = [
                            'rowIndex' => $index,
                            'row' => $data,
                            'type' => 'database',
                            'message' => $e->getMessage(),
                            'sqlState' => $e->errorInfo[0] ?? null,
                        ];
                    } catch (\Throwable $e) {
                        $errors[] = [
                            'rowIndex' => $index,
                            'row' => $data,
                            'type' => 'unknown',
                            'message' => $e->getMessage(),
                        ];
                    }
                }

                return response()->json([
                    'status' => empty($errors) ? 'ok' : (empty($stored) ? 'error' : 'partial'),
                    'mode'   => 'list',
                    'type'   => $config['type'] ?? null,
                    'count'  => count($rows),
                    'stored' => $stored,
                    'excelRow' => $data['__row'] ?? null,
                    'errors' => $errors,
                    'warnings' => $warnings,
                ], empty($errors) ? 200 : 207); // 207 Multi-Status si partiel
            case "single":
                $service = new GenericSingleImportService();
                try {
                    $data = $service->extract($file, $config);
                } catch (ValidationException $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => "Le fichier Excel est invalide ou illisible.",
                        'errors' => $e->errors(),
                    ], 422);
                } catch (\Throwable $e) {
                    Log::error('Import single: lecture du fichier échouée', [
                        'user_id' => Auth::id(),
                        'filename' => $file->getClientOriginalName(),
                        'message' => $e->getMessage(),
                    ]);
                    return response()->json([
                        'status' => 'error',
                        'message' => "Erreur lors de la lecture du fichier Excel.",
                    ], 422);
                }

                if (($config['type'] ?? null) === 'UE' && !empty($config['selectedProgrammeLink']) && is_array($config['selectedProgrammeLink'])) {
                    $selectedProgramme = $config['selectedProgrammeLink'];
                    $selectedCode = trim((string) ($selectedProgramme['code'] ?? ''));
                    $selectedLabel = trim((string) ($selectedProgramme['name'] ?? $selectedProgramme['libelle'] ?? ''));
                    $selectedSemesters = [];

                    if (!empty($selectedProgramme['semesters']) && is_array($selectedProgramme['semesters'])) {
                        foreach ($selectedProgramme['semesters'] as $sem) {
                            if (is_numeric($sem)) {
                                $semValue = (int) $sem;
                                if ($semValue > 0) {
                                    $selectedSemesters[] = $semValue;
                                }
                            }
                        }
                    } elseif (isset($selectedProgramme['semester']) && is_numeric($selectedProgramme['semester'])) {
                        $semValue = (int) $selectedProgramme['semester'];
                        if ($semValue > 0) {
                            $selectedSemesters[] = $semValue;
                        }
                    }

                    $selectedSemesters = array_values(array_unique($selectedSemesters));

                    if (!empty($selectedSemesters) && ($selectedCode !== '' || $selectedLabel !== '')) {
                        $data['links']['programmes'] = $data['links']['programmes'] ?? [];

                        foreach ($selectedSemesters as $selectedSemester) {
                            $alreadyExists = false;
                            foreach ($data['links']['programmes'] as $programme) {
                                $programmeCode = trim((string) ($programme['code'] ?? ''));
                                $programmeLabel = trim((string) ($programme['libelle'] ?? ''));
                                $programmeSemester = isset($programme['semestre']) && is_numeric($programme['semestre'])
                                    ? (int) $programme['semestre']
                                    : null;

                                if (
                                    $programmeSemester === $selectedSemester &&
                                    $programmeCode === $selectedCode &&
                                    $programmeLabel === $selectedLabel
                                ) {
                                    $alreadyExists = true;
                                    break;
                                }
                            }

                            if (!$alreadyExists) {
                                $data['links']['programmes'][] = [
                                    'code' => $selectedCode !== '' ? $selectedCode : null,
                                    'libelle' => $selectedLabel !== '' ? $selectedLabel : null,
                                    'semestre' => $selectedSemester,
                                ];
                            }
                        }
                    }
                }

                $warnings = [];
                switch ($config['type']) {
                    case 'UE':
                        [$stored, $warnings] = $this->normalizeStoreResult($this->storeUE($data));
                        break;

                    case 'AAT':
                        [$stored, $warnings] = $this->normalizeStoreResult($this->storeAAT($data));
                        break;

                    case 'AAV':
                        [$stored, $warnings] = $this->normalizeStoreResult($this->storeAAV($data));
                        break;

                    case 'PRE':
                        [$stored, $warnings] = $this->normalizeStoreResult($this->storePrerequis($data));
                        break;

                    case 'PRO':
                        [$stored, $warnings] = $this->normalizeStoreResult($this->storeProgramme($data));
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
                    'warnings' => $warnings,
                ]);


            default:
                return response()->json([
                    'message' => "importMode inconnu: {$config['importMode']}"
                ], 422);
        }
    }

    private function normalizeStoreResult($result): array
    {
        if (is_array($result) && array_key_exists('entity', $result)) {
            $warnings = $result['warnings'] ?? [];
            if (!is_array($warnings)) {
                $warnings = [];
            }
            return [$result['entity'], array_values($warnings)];
        }

        return [$result, []];
    }

    public function storeUE(array $values)
    {
        $uid = Auth::user()->university_id;

        //si on reçoit le nouveau format generic
        $type  = $values['type'] ?? 'UE';
        $main  = $values['main'] ?? [];
        $links = $values['links'] ?? [];

        if ($type !== 'UE') {
            throw ValidationException::withMessages([
                'type' => ["storeUE ne gère que le type UE. Reçu: {$type}"]
            ]);
        }

        // --------------------------
        // VALIDATION
        // --------------------------
        $messages = [
            'required' => 'Le champ :attribute est obligatoire.',
            'string'   => 'Le champ :attribute doit être un texte.',
            'integer'  => 'Le champ :attribute doit être un nombre.',
            'array'    => 'Le champ :attribute doit être une liste.',
            'in'       => 'Le champ :attribute doit être une des valeurs suivantes : :values.',
            'max'      => 'Le champ :attribute est trop long (max :max caractères).',
            'min'      => 'Le champ :attribute doit être un nombre (min :min).',

            'links.aats.*.contribution.integer' => 'La contribution de l’AAT doit être un nombre (1, 2 ou 3).',
            'links.aats.*.contribution.in'      => 'La contribution de l’AAT doit valoir 1, 2 ou 3.',

            'links.aavs.*.contribution.integer' => 'La contribution de l’AAV doit être un nombre (1, 2 ou 3).',
            'links.aavs.*.contribution.in'      => 'La contribution de l’AAV doit valoir 1, 2 ou 3.',

            'links.programmes.*.semestre.integer' => 'Le semestre du programme doit être un nombre.',
            'links.programmes.*.semestre.min'     => 'Le semestre du programme doit être au minimum 1.',
        ];

        $attributes = [
            'main.code'        => 'Sigle de l’UE',
            'main.name'        => 'Libellé de l’UE',
            'main.description' => 'Description de l’UE',
            'main.ects'        => 'ECTS de l’UE',

            'links.aats'               => 'Liste des AAT',
            'links.aats.*.code'        => 'Sigle de l’AAT',
            'links.aats.*.libelle'     => 'Libellé de l’AAT',
            'links.aats.*.contribution' => 'Contribution de l’AAT',

            'links.aavs'               => 'Liste des AAV',
            'links.aavs.*.code'        => 'Sigle de l’AAV',
            'links.aavs.*.libelle'     => 'Libellé de l’AAV',
            'links.aavs.*.contribution' => 'Contribution de l’AAV',

            'links.programmes'            => 'Liste des programmes',
            'links.programmes.*.code'     => 'Code du programme',
            'links.programmes.*.libelle'  => 'Libellé du programme',
            'links.programmes.*.semestre' => 'Semestre du programme',

            'links.prerequis'           => 'Liste des prérequis',
            'links.prerequis.*.code'    => 'Sigle du prérequis',
            'links.prerequis.*.libelle' => 'Libellé du prérequis',
        ];

        $validator = Validator::make($values, [
            'type' => 'required|in:UE',

            'main' => 'required|array',
            'main.code'        => 'nullable|string|max:50',
            'main.name'        => 'required|string|max:500',
            'main.description' => 'nullable|string',
            'main.ects'        => 'required|integer|min:1|max:120',

            'links' => 'nullable|array',

            'links.aats' => 'nullable|array',
            'links.aats.*.code' => 'nullable|string|max:50',
            'links.aats.*.libelle' => 'nullable|string|max:500',
            'links.aats.*.contribution' => 'nullable|integer|in:1,2,3',

            'links.aavs' => 'nullable|array',
            'links.aavs.*.code' => 'nullable|string|max:50',
            'links.aavs.*.libelle' => 'nullable|string|max:1000',
            'links.aavs.*.AATCode' => 'nullable|string|max:50',
            'links.aavs.*.contribution' => 'nullable|integer|in:1,2,3',

            'links.prerequis' => 'nullable|array',
            'links.prerequis.*.code' => 'nullable|string|max:50',
            'links.prerequis.*.libelle' => 'nullable|string|max:500',

            'links.programmes' => 'nullable|array',
            'links.programmes.*.code' => 'nullable|string|max:50',
            'links.programmes.*.libelle' => 'nullable|string|max:500',
            'links.programmes.*.semestre' => 'nullable|integer|min:1',
        ], $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $warnings = [];
        DB::beginTransaction();
        try {

            // --------------------------
            // 1) Création UE
            // --------------------------
            $ueCode = trim($main['code'] ?? '');
            $ueName = trim($main['name'] ?? '');
            $ueDesc = $main['description'] ?? null;
            $ueEcts = (int) ($main['ects'] ?? 0);

            // si vide => générer
            if ($ueCode === '') {
                $ueCode = $this->codeGen->nextUE();
            }

            try {
                $ue = UniteEnseignement::create([
                    'name' => $ueName,
                    'ects' => $ueEcts,
                    'code' => $ueCode,
                    'description' => $ueDesc,
                    'university_id' => $uid,
                ]);
            } catch (QueryException $e) {
                // duplicate key
                if (($e->errorInfo[0] ?? null) === '23000') {
                    throw ValidationException::withMessages([
                        'main.code' => ["Le sigle d’UE \"$ueCode\" existe déjà dans le logiciel, veuillez en fournir un différent."]
                    ]);
                }
                throw $e;
            }

            // -------------------------
            // 2) AATs liés
            // -------------------------
            $aats = $links['aats'] ?? [];
            if (!empty($aats)) {
                foreach ($aats as $aatData) {

                    $code = trim($aatData['code'] ?? '');
                    $name = trim($aatData['libelle'] ?? '');

                    if ($code === '' && $name === '') continue;

                    $aat = null;

                    if ($code !== '') {
                        $aat = AAT::where('code', $code)
                            ->where('university_id', $uid)
                            ->first();

                        if (!$aat) {
                            $warnings[] = "Le sigle AAT \"$code\" n'existe pas dans la base. Le lien n'a pas ete importe.";
                            continue;
                        }
                    } else {
                        $generatedCode = $this->codeGen->nextAAT();
                        $aat = AAT::create([
                            'code' => $generatedCode,
                            'name' => $name !== '' ? $name : null,
                            'university_id' => $uid,
                        ]);
                    }

                    if (($aat->name === null || trim((string)$aat->name) === '') && $name !== '') {
                        $aat->name = $name;
                        $aat->save();
                    }

                    $contribution = is_numeric($aatData['contribution'] ?? null)
                        ? (int) $aatData['contribution']
                        : null;

                    $ue->aat()->syncWithoutDetaching([
                        $aat->id => [
                            'contribution' => $contribution,
                            'university_id' => $uid,
                        ]
                    ]);
                }
            }

            // -------------------------
            // 3) Programmes liés
            // -------------------------
            $programmes = $links['programmes'] ?? [];
            if (!empty($programmes)) {
                foreach ($programmes as $proData) {
                    $code = trim($proData['code'] ?? '');
                    $name = trim($proData['libelle'] ?? '');
                    $sem  = isset($proData['semestre']) && is_numeric($proData['semestre'])
                        ? (int) $proData['semestre']
                        : null;

                    if ($code === '' && $name === '') continue;

                    if ($code === '' && $name !== '') {
                        $code = $this->codeGen->nextProgramme();
                    }

                    $pro = Programme::where('code', $code)
                        ->where('university_id', $uid)
                        ->first();

                    if (!$pro && $name === '') continue;

                    if (!$pro) {
                        $pro = Programme::create([
                            'code' => $code,
                            'name' => $name !== '' ? $name : null,
                            'university_id' => $uid,
                        ]);
                    } elseif (($pro->name === null || trim((string)$pro->name) === '') && $name !== '') {
                        $pro->name = $name;
                        $pro->save();
                    }

                    if ($sem === null) {
                        $warnings[] = "Aucun semestre fourni pour le programme \"$code\". Le lien UE-programme n'a pas ete importe.";
                        continue;
                    }

                    $proSemester = DB::table('pro_semester')
                        ->where('fk_programme', $pro->id)
                        ->where('semester', $sem)
                        ->first(['id']);

                    if (!$proSemester) {
                        $warnings[] = "Semestre $sem introuvable pour le programme \"$code\". Le lien UE-programme n'a pas ete importe.";
                        continue;
                    }

                    $pivot = [
                        'university_id' => $uid,
                        'fk_semester' => (int) $proSemester->id,
                    ];

                    $ue->pro()->syncWithoutDetaching([
                        $pro->id => $pivot,
                    ]);
                }
            }

            // -------------------------
            // 4) AAVs liés
            // -------------------------
            $aavs = $links['aavs'] ?? [];
            if (!empty($aavs)) {
                foreach ($aavs as $aavData) {
                    $code = trim($aavData['code'] ?? '');
                    $name = trim($aavData['libelle'] ?? '');

                    if ($code === '' && $name === '') continue;

                    if ($code === '' && $name !== '') {
                        $code = $this->codeGen->nextAAV();
                    }

                    $aav = AcquisApprentissageVise::where('code', $code)
                        ->where('university_id', $uid)
                        ->first();

                    if (!$aav && $name === '') continue;

                    if (!$aav) {
                        $aav = AcquisApprentissageVise::create([
                            'code' => $code,
                            'name' => $name !== '' ? $name : null,
                            'university_id' => $uid,
                        ]);
                    } elseif (($aav->name === null || trim((string)$aav->name) === '') && $name !== '') {
                        $aav->name = $name;
                        $aav->save();
                    }

                    $ue->aavvise()->syncWithoutDetaching([
                        $aav->id => ['university_id' => $uid]
                    ]);
                }
            }

            // -------------------------
            // 5) Prérequis liés
            // -------------------------
            $prerequis = $links['prerequis'] ?? [];
            if (!empty($prerequis)) {
                foreach ($prerequis as $preData) {
                    $code = trim($preData['code'] ?? '');
                    $name = trim($preData['libelle'] ?? '');

                    if ($code === '' && $name === '') continue;

                    if ($code === '' && $name !== '') {
                        $code = $this->codeGen->nextPrerequis();
                    }

                    $pre = AcquisApprentissageVise::where('code', $code)
                        ->where('university_id', $uid)
                        ->first();

                    if (!$pre && $name === '') continue;

                    if (!$pre) {
                        $pre = AcquisApprentissageVise::create([
                            'code' => $code,
                            'name' => $name !== '' ? $name : null,
                            'university_id' => $uid,
                        ]);
                    } elseif (($pre->name === null || trim((string)$pre->name) === '') && $name !== '') {
                        $pre->name = $name;
                        $pre->save();
                    }

                    $ue->prerequis()->syncWithoutDetaching([
                        $pre->id => ['university_id' => $uid]
                    ]);
                }
            }

            DB::commit();
            return $ue;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }


    public function storeAAT(array $values)
    {
        $uid   = Auth::user()->university_id;
        $type  = $values['type'] ?? 'AAT';
        $main  = $values['main'] ?? [];
        $links = $values['links'] ?? [];

        if ($type !== 'AAT') {
            throw ValidationException::withMessages([
                'type' => ["storeAAT ne gère que le type AAT. Reçu: {$type}"]
            ]);
        }

        // --------------------------
        // VALIDATION
        // --------------------------
        $messages = [
            'required' => 'Le champ :attribute est obligatoire.',
            'string'   => 'Le champ :attribute doit être un texte.',
            'array'    => 'Le champ :attribute doit être une liste.',
            'max'      => 'Le champ :attribute est trop long (max :max caractères).',
        ];

        $attributes = [
            'main.code' => 'Sigle AAT',
            'main.name' => 'Libellé AAT',
            'main.description' => 'Description AAT',

            'links.ues' => 'Liste des UE liées',
            'links.ues.*.code' => 'Sigle UE',
            'links.ues.*.libelle' => 'Libellé UE',

            'links.aavs' => 'Liste des AAV liés',
            'links.aavs.*.code' => 'Sigle AAV',
            'links.aavs.*.libelle' => 'Libellé AAV',
        ];

        $validator = Validator::make($values, [
            'type' => 'required|in:AAT',

            'main' => 'required|array',
            'main.code' => 'nullable|string|max:50',
            'main.name' => 'required|string|max:500',
            'main.description' => 'nullable|string',

            'links' => 'nullable|array',

            // liens possibles depuis GenericSingleImportService
            'links.ues' => 'nullable|array',
            'links.ues.*.code' => 'nullable|string|max:50',
            'links.ues.*.libelle' => 'nullable|string|max:500',

            'links.aavs' => 'nullable|array',
            'links.aavs.*.code' => 'nullable|string|max:50',
            'links.aavs.*.libelle' => 'nullable|string|max:1000',
        ], $messages, $attributes);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // --------------------------
        // 1) Création AAT
        // --------------------------
        DB::beginTransaction();
        try {

            $aatCode = trim($main['code'] ?? '');
            $aatName = trim($main['name'] ?? '');
            $aatDesc = $main['description'] ?? null;

            if ($aatCode === '') {
                $aatCode = $this->codeGen->nextAAT();
            }

            try {
                $aat = AAT::create([
                    'code' => $aatCode,
                    'name' => $aatName,
                    'description' => $aatDesc,
                    'university_id' => $uid,
                ]);
            } catch (QueryException $e) {
                if (($e->errorInfo[0] ?? null) === '23000') {
                    throw ValidationException::withMessages([
                        'main.code' => ["Le sigle AAT \"$aatCode\" existe déjà dans le logiciel, veuillez en fournir un différent."]
                    ]);
                }
                throw $e;
            }

            // --------------------------
            // 2) Lier les UE (pivot ue_aat)
            // --------------------------
            $ues = $links['ues'] ?? [];
            if (!empty($ues)) {
                foreach ($ues as $ueData) {
                    $ueCode = trim($ueData['code'] ?? '');
                    $ueName = trim($ueData['libelle'] ?? '');

                    if ($ueCode === '' && $ueName === '') continue;

                    if ($ueCode === '' && $ueName !== '') {
                        $ueCode = $this->codeGen->nextUE();
                    }

                    // UE scoped université
                    $ue = UniteEnseignement::where('code', $ueCode)
                        ->where('university_id', $uid)
                        ->first();

                    // pas trouvé + pas de libellé => ignore
                    if (!$ue && $ueName === '') continue;

                    if (!$ue) {
                        $ue = UniteEnseignement::create([
                            'code' => $ueCode,
                            'name' => $ueName !== '' ? $ueName : null,
                            'university_id' => $uid,
                            //  ects/description peuvent être nullables selon ta table
                            'ects' => 1, // <-- ajuste si ects est NOT NULL
                        ]);
                    } elseif (($ue->name === null || trim((string)$ue->name) === '') && $ueName !== '') {
                        $ue->name = $ueName;
                        $ue->save();
                    }

                    // pivot défini sur UniteEnseignement::aat()
                    $ue->aat()->syncWithoutDetaching([
                        $aat->id => [
                            // si ton pivot contient university_id, garde-le
                            'university_id' => $uid,
                        ]
                    ]);
                }
            }

            // --------------------------
            // 3) Lier les AAV (pivot AAT <-> AAV)
            // --------------------------
            $aavs = $links['aavs'] ?? [];
            if (!empty($aavs)) {
                foreach ($aavs as $aavData) {
                    $aavCode = trim($aavData['code'] ?? '');
                    $aavName = trim($aavData['libelle'] ?? '');

                    if ($aavCode === '' && $aavName === '') continue;

                    if ($aavCode === '' && $aavName !== '') {
                        $aavCode = $this->codeGen->nextAAV();
                    }

                    $aav = AcquisApprentissageVise::where('code', $aavCode)
                        ->where('university_id', $uid)
                        ->first();

                    if (!$aav && $aavName === '') continue;

                    if (!$aav) {
                        $aav = AcquisApprentissageVise::create([
                            'code' => $aavCode,
                            'name' => $aavName !== '' ? $aavName : null,
                            'university_id' => $uid,
                        ]);
                    } elseif (($aav->name === null || trim((string)$aav->name) === '') && $aavName !== '') {
                        $aav->name = $aavName;
                        $aav->save();
                    }

                    if (method_exists($aat, 'aav')) {
                        $aat->aav()->syncWithoutDetaching([
                            $aav->id => [
                                'university_id' => $uid, // si pivot a ce champ
                            ]
                        ]);
                    }
                }
            }

            DB::commit();
            return $aat;
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
