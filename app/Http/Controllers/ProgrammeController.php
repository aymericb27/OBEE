<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise;
use App\Models\Pro_semester;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use App\Services\CodeGeneratorService;
use App\Services\UEAnomalyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProgrammeController extends Controller
{
    public function __construct(
        private CodeGeneratorService $codeGen,
        private UEAnomalyService $ueAnomalyService
    ) {}

    public function get(Request $request = null)
    {
        $programmes = Programme::select('code', 'id', 'ects', 'name')->get();
        return $programmes;
    }


    public function addUES(Request $request)
    {
        $validated = $request->validate([
            'programme_id' => 'required|integer|exists:programme,id',
            'semester' => 'required|integer|min:1',
            'list' => 'required|array|min:1',
            'list.*.id' => 'required|integer|exists:unite_enseignement,id',
        ]);

        $universityId = Auth::user()->university_id;

        // 🔹 récupérer le pro_semester correspondant
        $proSemester = DB::table('pro_semester')
            ->where('fk_programme', $validated['programme_id'])
            ->where('semester', $validated['semester'])
            ->where('university_id', $universityId)
            ->first();

        if (!$proSemester) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable pour ce programme',
            ], 404);
        }

        $maxOrder = (int) DB::table('ue_programme')
            ->where('fk_programme', $validated['programme_id'])
            ->where('fk_semester', $proSemester->id)
            ->where('university_id', $universityId)
            ->max('display_order');

        $requestedUeIds = collect($validated['list'])
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $existingUeIds = DB::table('ue_programme')
            ->where('fk_programme', $validated['programme_id'])
            ->where('fk_semester', $proSemester->id)
            ->where('university_id', $universityId)
            ->whereIn('fk_unite_enseignement', $requestedUeIds)
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->all();

        $existingLookup = array_flip($existingUeIds);
        $rows = [];

        foreach ($requestedUeIds as $ueId) {
            if (isset($existingLookup[$ueId])) {
                continue;
            }

            $maxOrder++;
            $rows[] = [
                'fk_unite_enseignement' => $ueId,
                'fk_programme' => $validated['programme_id'],
                'fk_semester' => $proSemester->id,
                'university_id' => $universityId,
                'display_order' => $maxOrder,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($rows)) {
            DB::table('ue_programme')->insert($rows);
            $this->ueAnomalyService->recomputeForUEIds(
                collect($rows)->pluck('fk_unite_enseignement')->map(fn($id) => (int) $id)->all()
            );
        }
        $this->ueAnomalyService->recomputeEmptySemesterAnomaliesForUniversity((int) $universityId);

        return response()->json([
            'success' => true,
            'message' => 'UE ajoutées au programme avec succès',
            'added' => count($rows),
        ]);
    }

    public function getPrerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ues = AcquisApprentissageVise::select('acquis_apprentissage_vise.id','code','name')->join('aavpro_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')->where('fk_programme', $validated['id'])->get();
        return $ues;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|min:0',
            'semestre' => 'required|integer|between:2,10',
            'semestresCredits' => 'required|array',
            'semestresCredits.*' => 'required|integer|min:0',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'code' => 'nullable|string|max:50',
        ]);

        // ✅ si vide => générer
        if (empty($validated['code'])) {
            $validated['code'] = $this->codeGen->nextProgramme(); // (si tu as un nextProgramme(), mets-le ici)
        }

        $validated['university_id'] = Auth::user()->university_id;

        $response = DB::transaction(function () use ($validated) {
            // ----- Création du programme -----
            $programme = Programme::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'ects' => $validated['ects'],
                'university_id' => $validated['university_id'],
            ]);

            // ----- Création des semestres (pro_semester) -----
            $rows = [];
            for ($i = 1; $i <= (int) $validated['semestre']; $i++) {
                $rows[] = [
                    'ects' => (int) ($validated['semestresCredits'][$i] ?? 0),
                    'semester' => $i,
                    'fk_programme' => $programme->id,
                    'university_id' => $validated['university_id'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('pro_semester')->insert($rows);

            if (isset($validated['aavprerequis'])) {
                $pivotData = [];
                foreach ($validated['aavprerequis'] as $item) {
                    $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
                }
                $programme->prerequis()->sync($pivotData);
            }
            return response()->json([
                'success' => true,
                'message' => 'Le programme a été créé correctement',
                'id' => $programme->id,
            ]);
        });

        $this->ueAnomalyService->recomputeEmptySemesterAnomaliesForUniversity((int) $validated['university_id']);

        return $response;
    }


    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
        ]);
        $universityId = (int) Auth::user()->university_id;
        $impactedUeIds = DB::table('ue_programme')
            ->where('university_id', $universityId)
            ->where('fk_programme', (int) $validated['id'])
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $pro = Programme::findOrFail($validated['id']);
        $pro->delete();

        $this->ueAnomalyService->recomputeForUEIds($impactedUeIds);
        $this->ueAnomalyService->recomputeEmptySemesterAnomaliesForUniversity($universityId);

        return response()->json([
            'success' => true,
            'message' => "Programme supprimé avec succès.",
        ]);
    }
    /**
     * Update an existing programme
     */

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|min:0',

            // le front envoie ça
            'semestre' => 'required|integer|between:2,10',
            'semestresCredits' => 'required|array',
            'semestresCredits.*' => 'required|integer|min:0',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
        ]);

        $programme = Programme::findOrFail($validated['id']);
        $universityId = Auth::user()->university_id;

        $existingCode = Programme::where('university_id', $universityId)
            ->where('code', $validated['code'])
            ->where('id', '!=', $programme->id)
            ->exists();

        if ($existingCode) {
            return response()->json([
                'success' => false,
                'message' => 'Le sigle existe deja pour cette universite.',
            ], 422);
        }

        $impactedUeIdsBefore = DB::table('ue_programme')
            ->where('university_id', $universityId)
            ->where('fk_programme', (int) $programme->id)
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $response = DB::transaction(function () use ($validated, $programme, $universityId) {

            // ✅ update programme (sans semestre)
            $programme->update([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'ects' => $validated['ects'],
            ]);

            // ✅ conserver les semestres existants pour préserver les liens UE
            $existingSemesters = DB::table('pro_semester')
                ->where('fk_programme', $programme->id)
                ->where('university_id', $universityId)
                ->get()
                ->keyBy('semester');

            $keptSemesterIds = [];
            for ($i = 1; $i <= (int) $validated['semestre']; $i++) {
                $ects = (int) ($validated['semestresCredits'][$i] ?? 0);

                if (isset($existingSemesters[$i])) {
                    $semesterRow = $existingSemesters[$i];
                    DB::table('pro_semester')
                        ->where('id', $semesterRow->id)
                        ->update([
                            'ects' => $ects,
                            'updated_at' => now(),
                        ]);
                    $keptSemesterIds[] = $semesterRow->id;
                    continue;
                }

                $keptSemesterIds[] = DB::table('pro_semester')->insertGetId([
                    'ects' => $ects,
                    'semester' => $i,
                    'fk_programme' => $programme->id,
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // ✅ supprimer uniquement les semestres retirés de la maquette
            DB::table('pro_semester')
                ->where('fk_programme', $programme->id)
                ->where('university_id', $universityId)
                ->whereNotIn('id', $keptSemesterIds)
                ->delete();

            if (isset($validated['aavprerequis'])) {
                $pivotData = [];
                foreach ($validated['aavprerequis'] as $item) {
                    $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
                }
                $programme->prerequis()->sync($pivotData);
            }
            return response()->json([
                'success' => true,
                'message' => 'Programme modifié avec succès',
                'id' => $programme->id,
            ]);
        });

        $impactedUeIdsAfter = DB::table('ue_programme')
            ->where('university_id', $universityId)
            ->where('fk_programme', (int) $programme->id)
            ->pluck('fk_unite_enseignement')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $this->ueAnomalyService->recomputeForUEIds(array_values(array_unique(array_merge(
            $impactedUeIdsBefore,
            $impactedUeIdsAfter
        ))));
        $this->ueAnomalyService->recomputeEmptySemesterAnomaliesForUniversity((int) $universityId);

        return $response;
    }

    public function copy(Request $request)
    {
        $validated = $request->validate([
            'source_id' => 'required|integer|exists:programme,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
        ]);

        $universityId = Auth::user()->university_id;

        $copiedUeIds = [];

        $response = DB::transaction(function () use ($validated, $universityId, &$copiedUeIds) {
            $sourceProgramme = Programme::where('id', $validated['source_id'])
                ->where('university_id', $universityId)
                ->firstOrFail();

            $existingCode = Programme::where('university_id', $universityId)
                ->where('code', $validated['code'])
                ->exists();

            if ($existingCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le sigle existe deja pour cette universite.',
                ], 422);
            }

            $newProgramme = Programme::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'ects' => $sourceProgramme->ects,
                'university_id' => $universityId,
            ]);

            $sourceSemesters = DB::table('pro_semester')
                ->where('fk_programme', $sourceProgramme->id)
                ->where('university_id', $universityId)
                ->orderBy('semester')
                ->get();

            $semesterIdMap = [];
            foreach ($sourceSemesters as $semester) {
                $newSemesterId = DB::table('pro_semester')->insertGetId([
                    'ects' => $semester->ects,
                    'semester' => $semester->semester,
                    'fk_programme' => $newProgramme->id,
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $semesterIdMap[$semester->id] = $newSemesterId;
            }

            $sourcePrerequis = DB::table('aavpro_prerequis')
                ->where('fk_programme', $sourceProgramme->id)
                ->where('university_id', $universityId)
                ->get();

            if ($sourcePrerequis->isNotEmpty()) {
                $prerequisRows = [];
                foreach ($sourcePrerequis as $prerequis) {
                    $prerequisRows[] = [
                        'fk_acquis_apprentissage_prerequis' => $prerequis->fk_acquis_apprentissage_prerequis,
                        'fk_programme' => $newProgramme->id,
                        'university_id' => $universityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('aavpro_prerequis')->insert($prerequisRows);
            }

            $sourceUes = DB::table('ue_programme')
                ->where('fk_programme', $sourceProgramme->id)
                ->where('university_id', $universityId)
                ->get();

            if ($sourceUes->isNotEmpty()) {
                $ueRows = [];
                foreach ($sourceUes as $ueLink) {
                    if (!isset($semesterIdMap[$ueLink->fk_semester])) {
                        continue;
                    }
                    $ueRows[] = [
                        'fk_unite_enseignement' => $ueLink->fk_unite_enseignement,
                        'fk_programme' => $newProgramme->id,
                        'fk_semester' => $semesterIdMap[$ueLink->fk_semester],
                        'display_order' => $ueLink->display_order ?? 0,
                        'university_id' => $universityId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($ueRows)) {
                    DB::table('ue_programme')->insert($ueRows);
                    $copiedUeIds = collect($ueRows)
                        ->pluck('fk_unite_enseignement')
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values()
                        ->all();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Programme copié avec succes',
                'id' => $newProgramme->id,
            ]);
        });

        if (!empty($copiedUeIds)) {
            $this->ueAnomalyService->recomputeForUEIds($copiedUeIds);
        }
        $this->ueAnomalyService->recomputeEmptySemesterAnomaliesForUniversity((int) $universityId);

        return $response;
    }


    public function getUE(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ues = UniteEnseignement::select(
            'unite_enseignement.id',
            'unite_enseignement.code',
            'unite_enseignement.name',
            'unite_enseignement.ects',
            'unite_enseignement.university_id',
            'unite_enseignement.created_at',
            'unite_enseignement.updated_at'
        )
            ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('ue_programme.fk_programme', $validated['id'])
            ->distinct()
            ->get();
        $this->attachAnomalySummaryToUes($ues, (int) Auth::user()->university_id);
        return $ues;
    }

    public function getTree(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $programme = Programme::select('id', 'code', 'name', 'ects')
            ->where('id', $validated['id'])
            ->firstOrFail();

        $semesters = DB::table('pro_semester')
            ->where('fk_programme', $programme->id)
            ->orderBy('semester')
            ->get();

        $listSemestre = [];
        $semesterIds = $semesters->pluck('id')->map(fn($id) => (int) $id)->all();
        $semesterAnomalyMap = $this->ueAnomalyService->getSummaryForSemesterIds(
            $semesterIds,
            (int) Auth::user()->university_id
        );

        foreach ($semesters as $sem) {
            $ues = $this->getUEBySemester($programme->id, $sem->id);

            $listSemestre[] = [
                'id' => $sem->id,
                'number' => $sem->semester,
                'ects' => $sem->ects,
                'UES' => $ues,
                'countECTS' => $ues->sum('ects'),
                'anomaly_summary' => $semesterAnomalyMap->get((int) $sem->id, [
                    'has_anomaly' => false,
                    'count' => 0,
                    'severity' => 'info',
                ]),
            ];
        }

        $allUes = collect($listSemestre)->flatMap(function ($sem) {
            return collect($sem['UES'] ?? []);
        });
        $this->attachAnomalySummaryToUes($allUes, (int) Auth::user()->university_id, true);

        // ✅ retourne un array (pas mutation d'attribut dynamique)
        return response()->json([
            'id' => $programme->id,
            'code' => $programme->code,
            'name' => $programme->name,
            'ects' => $programme->ects,
            'listSemestre' => $listSemestre,
        ]);
    }

    public function getAnalysis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'anomaly_code' => 'nullable|string|max:50',
        ]);

        $universityId = (int) Auth::user()->university_id;
        $programId = (int) $validated['id'];
        $selectedAnomalyCode = trim((string) ($validated['anomaly_code'] ?? ''));
        if ($selectedAnomalyCode === '') {
            $selectedAnomalyCode = null;
        }

        $programme = Programme::select('id', 'code', 'name')
            ->where('id', $programId)
            ->firstOrFail();

        $semesters = DB::table('pro_semester')
            ->select('id', 'semester')
            ->where('fk_programme', $programId)
            ->where('university_id', $universityId)
            ->orderBy('semester')
            ->get();

        $semesterRows = [];
        $allUeIds = [];
        foreach ($semesters as $semester) {
            $ues = $this->getUEBySemester($programId, (int) $semester->id);
            $ueEntries = collect($ues)
                ->flatMap(function ($ue) {
                    $items = collect([[
                        'id' => (int) $ue->id,
                        'code' => (string) $ue->code,
                        'name' => (string) $ue->name,
                    ]]);

                    $children = collect($ue->children ?? [])
                        ->map(fn($child) => [
                            'id' => (int) $child->id,
                            'code' => (string) $child->code,
                            'name' => (string) $child->name,
                        ]);

                    return $items->merge($children);
                })
                ->unique('id')
                ->values()
                ->all();

            $semesterRows[] = [
                'id' => (int) $semester->id,
                'number' => (int) $semester->semester,
                'ues' => $ueEntries,
            ];

            $allUeIds = array_merge(
                $allUeIds,
                collect($ueEntries)->pluck('id')->map(fn($id) => (int) $id)->all()
            );
        }

        $allUeIds = collect($allUeIds)->unique()->values()->all();

        $anomalyRows = DB::table('anomalies')
            ->select('ue_id', 'code')
            ->where('university_id', $universityId)
            ->where('is_resolved', false)
            ->where('code', '!=', UEAnomalyService::CODE_HAS_ANOMALY)
            ->whereIn('ue_id', empty($allUeIds) ? [-1] : $allUeIds)
            ->get();

        $anomalyCodesByUe = $anomalyRows
            ->groupBy('ue_id')
            ->map(fn($group) => collect($group)->pluck('code')->unique()->values()->all())
            ->all();

        $availableAnomalyCodes = collect($anomalyRows)
            ->pluck('code')
            ->unique()
            ->sort()
            ->values()
            ->map(fn($code) => [
                'code' => $code,
                'label' => $this->anomalyCodeLabel((string) $code),
            ])
            ->all();

        $semestersWithAnyAnomaly = [];
        $semestersWithSelectedAnomaly = [];

        foreach ($semesterRows as $semesterRow) {
            $any = [];
            $selected = [];

            foreach ($semesterRow['ues'] as $ue) {
                $ueId = (int) ($ue['id'] ?? 0);
                $codes = $anomalyCodesByUe[$ueId] ?? [];
                if (empty($codes)) {
                    continue;
                }

                $item = [
                    'id' => $ueId,
                    'code' => (string) ($ue['code'] ?? ''),
                    'name' => (string) ($ue['name'] ?? ''),
                    'anomaly_codes' => $codes,
                    'anomaly_count' => count($codes),
                ];

                $any[] = $item;

                if ($selectedAnomalyCode !== null && in_array($selectedAnomalyCode, $codes, true)) {
                    $selected[] = $item;
                }
            }

            if (!empty($any)) {
                $semestersWithAnyAnomaly[] = [
                    'id' => $semesterRow['id'],
                    'number' => $semesterRow['number'],
                    'ues' => $any,
                ];
            }

            if (!empty($selected)) {
                $semestersWithSelectedAnomaly[] = [
                    'id' => $semesterRow['id'],
                    'number' => $semesterRow['number'],
                    'ues' => $selected,
                ];
            }
        }

        $semesterByUeId = [];
        foreach ($semesterRows as $semesterRow) {
            foreach ($semesterRow['ues'] as $ue) {
                $ueId = (int) ($ue['id'] ?? 0);
                if ($ueId > 0 && !array_key_exists($ueId, $semesterByUeId)) {
                    $semesterByUeId[$ueId] = (int) $semesterRow['number'];
                }
            }
        }

        $aatRows = DB::table('acquis_apprentissage_terminaux')
            ->select('id', 'code', 'name', 'level_contribution')
            ->where('university_id', $universityId)
            ->where(function ($query) use ($programId, $universityId) {
                $query->whereExists(function ($sub) use ($programId, $universityId) {
                    $sub->select(DB::raw(1))
                        ->from('ue_aat')
                        ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'ue_aat.fk_ue')
                        ->whereColumn('ue_aat.fk_aat', 'acquis_apprentissage_terminaux.id')
                        ->where('ue_aat.university_id', $universityId)
                        ->where('ue_programme.university_id', $universityId)
                        ->where('ue_programme.fk_programme', $programId);
                })->orWhereExists(function ($sub) use ($programId, $universityId) {
                    $sub->select(DB::raw(1))
                        ->from('aav_aat')
                        ->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'aav_aat.fk_aav')
                        ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'aavue_vise.fk_unite_enseignement')
                        ->whereColumn('aav_aat.fk_aat', 'acquis_apprentissage_terminaux.id')
                        ->where('aav_aat.university_id', $universityId)
                        ->where('aavue_vise.university_id', $universityId)
                        ->where('ue_programme.university_id', $universityId)
                        ->where('ue_programme.fk_programme', $programId)
                        ->where(function ($nested) use ($programId) {
                            $nested->whereNull('aav_aat.fk_programme')
                                ->orWhere('aav_aat.fk_programme', $programId);
                        });
                });
            })
            ->orderBy('code')
            ->get()
            ->map(fn($aat) => [
                'id' => (int) $aat->id,
                'code' => (string) $aat->code,
                'name' => (string) $aat->name,
                'level_contribution' => $aat->level_contribution !== null ? (int) $aat->level_contribution : null,
            ])
            ->values()
            ->all();

        $aavRows = DB::table('aavue_vise as av')
            ->join('acquis_apprentissage_vise as aav', 'aav.id', '=', 'av.fk_acquis_apprentissage_vise')
            ->join('unite_enseignement as ue', 'ue.id', '=', 'av.fk_unite_enseignement')
            ->where('av.university_id', $universityId)
            ->whereIn('av.fk_unite_enseignement', empty($allUeIds) ? [-1] : $allUeIds)
            ->select(
                'av.fk_unite_enseignement as ue_id',
                'ue.code as ue_code',
                'ue.name as ue_name',
                'aav.id as aav_id',
                'aav.code as aav_code',
                'aav.name as aav_name'
            )
            ->orderBy('ue.code')
            ->orderBy('aav.code')
            ->get();

        $aatIds = collect($aatRows)->pluck('id')->map(fn($id) => (int) $id)->all();
        $aavIds = $aavRows->pluck('aav_id')->map(fn($id) => (int) $id)->unique()->values()->all();

        $contributionRows = DB::table('aav_aat')
            ->select('fk_aav', 'fk_aat', 'fk_programme', 'contribution')
            ->where('university_id', $universityId)
            ->whereIn('fk_aav', empty($aavIds) ? [-1] : $aavIds)
            ->whereIn('fk_aat', empty($aatIds) ? [-1] : $aatIds)
            ->where(function ($query) use ($programId) {
                $query->whereNull('fk_programme')
                    ->orWhere('fk_programme', $programId);
            })
            ->orderByRaw('CASE WHEN fk_programme IS NULL THEN 0 ELSE 1 END DESC')
            ->get();

        $contributionByAav = [];
        foreach ($contributionRows as $row) {
            $aavId = (int) $row->fk_aav;
            $aatId = (int) $row->fk_aat;
            if (!isset($contributionByAav[$aavId])) {
                $contributionByAav[$aavId] = [];
            }
            if (array_key_exists($aatId, $contributionByAav[$aavId])) {
                continue;
            }
            $contributionByAav[$aavId][$aatId] = $row->contribution !== null ? (int) $row->contribution : null;
        }

        $matrixRows = $aavRows
            ->map(function ($row) use ($semesterByUeId, $aatIds, $contributionByAav) {
                $ueId = (int) $row->ue_id;
                $aavId = (int) $row->aav_id;
                $contrib = [];

                foreach ($aatIds as $aatId) {
                    $contrib[(int) $aatId] = $contributionByAav[$aavId][(int) $aatId] ?? null;
                }

                return [
                    'semester_number' => $semesterByUeId[$ueId] ?? null,
                    'ue_id' => $ueId,
                    'ue_code' => (string) $row->ue_code,
                    'ue_name' => (string) $row->ue_name,
                    'aav_id' => $aavId,
                    'aav_code' => (string) $row->aav_code,
                    'aav_name' => (string) $row->aav_name,
                    'contributions' => $contrib,
                ];
            })
            ->sortBy([
                ['semester_number', 'asc'],
                ['ue_code', 'asc'],
                ['aav_code', 'asc'],
            ])
            ->values()
            ->all();

        return response()->json([
            'program' => [
                'id' => (int) $programme->id,
                'code' => (string) $programme->code,
                'name' => (string) $programme->name,
            ],
            'available_anomaly_codes' => $availableAnomalyCodes,
            'selected_anomaly_code' => $selectedAnomalyCode,
            'semesters_with_anomalies' => $semestersWithAnyAnomaly,
            'semesters_with_specific_anomaly' => $semestersWithSelectedAnomaly,
            'aav_aat_contribution_matrix' => [
                'aats' => $aatRows,
                'rows' => $matrixRows,
            ],
        ]);
    }

    public function getAnalysisUesWithErrors(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
        ]);

        $universityId = (int) Auth::user()->university_id;
        $programId = (int) $validated['id'];
        $programme = $this->loadProgramForAnalysis($programId);

        ['semester_rows' => $semesterRows, 'all_ue_ids' => $allUeIds] =
            $this->loadSemesterUeEntriesForAnalysis($programId, $universityId);

        ['anomaly_codes_by_ue' => $anomalyCodesByUe] =
            $this->loadAnomalyDataForUes($allUeIds, $universityId);

        $semestersWithAnyAnomaly = [];
        foreach ($semesterRows as $semesterRow) {
            $items = [];
            foreach (($semesterRow['ues'] ?? []) as $ue) {
                $ueId = (int) ($ue['id'] ?? 0);
                $codes = $anomalyCodesByUe[$ueId] ?? [];
                if (empty($codes)) {
                    continue;
                }
                $items[] = [
                    'id' => $ueId,
                    'code' => (string) ($ue['code'] ?? ''),
                    'name' => (string) ($ue['name'] ?? ''),
                    'anomaly_codes' => $codes,
                    'anomaly_count' => count($codes),
                ];
            }

            if (!empty($items)) {
                $semestersWithAnyAnomaly[] = [
                    'id' => $semesterRow['id'],
                    'number' => $semesterRow['number'],
                    'ues' => $items,
                ];
            }
        }

        return response()->json([
            'program' => [
                'id' => (int) $programme->id,
                'code' => (string) $programme->code,
                'name' => (string) $programme->name,
            ],
            'semesters_with_anomalies' => $semestersWithAnyAnomaly,
        ]);
    }

    public function getAnalysisUesWithSpecificError(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'anomaly_code' => 'nullable|string|max:50',
        ]);

        $universityId = (int) Auth::user()->university_id;
        $programId = (int) $validated['id'];
        $selectedAnomalyCode = trim((string) ($validated['anomaly_code'] ?? ''));
        if ($selectedAnomalyCode === '') {
            $selectedAnomalyCode = null;
        }

        $programme = $this->loadProgramForAnalysis($programId);

        ['semester_rows' => $semesterRows, 'all_ue_ids' => $allUeIds] =
            $this->loadSemesterUeEntriesForAnalysis($programId, $universityId);

        [
            'anomaly_codes_by_ue' => $anomalyCodesByUe,
            'available_anomaly_codes' => $availableAnomalyCodes,
        ] = $this->loadAnomalyDataForUes($allUeIds, $universityId);

        $semestersWithSelectedAnomaly = [];
        if ($selectedAnomalyCode !== null) {
            foreach ($semesterRows as $semesterRow) {
                $items = [];
                foreach (($semesterRow['ues'] ?? []) as $ue) {
                    $ueId = (int) ($ue['id'] ?? 0);
                    $codes = $anomalyCodesByUe[$ueId] ?? [];
                    if (!in_array($selectedAnomalyCode, $codes, true)) {
                        continue;
                    }
                    $items[] = [
                        'id' => $ueId,
                        'code' => (string) ($ue['code'] ?? ''),
                        'name' => (string) ($ue['name'] ?? ''),
                        'anomaly_codes' => $codes,
                        'anomaly_count' => count($codes),
                    ];
                }

                if (!empty($items)) {
                    $semestersWithSelectedAnomaly[] = [
                        'id' => $semesterRow['id'],
                        'number' => $semesterRow['number'],
                        'ues' => $items,
                    ];
                }
            }
        }

        return response()->json([
            'program' => [
                'id' => (int) $programme->id,
                'code' => (string) $programme->code,
                'name' => (string) $programme->name,
            ],
            'available_anomaly_codes' => $availableAnomalyCodes,
            'selected_anomaly_code' => $selectedAnomalyCode,
            'semesters_with_specific_anomaly' => $semestersWithSelectedAnomaly,
        ]);
    }

    public function getAnalysisContributionMatrix(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
        ]);

        $universityId = (int) Auth::user()->university_id;
        $programId = (int) $validated['id'];
        $programme = $this->loadProgramForAnalysis($programId);

        ['semester_rows' => $semesterRows, 'all_ue_ids' => $allUeIds] =
            $this->loadSemesterUeEntriesForAnalysis($programId, $universityId);

        $matrix = $this->buildAavAatContributionMatrix($semesterRows, $allUeIds, $programId, $universityId);

        return response()->json([
            'program' => [
                'id' => (int) $programme->id,
                'code' => (string) $programme->code,
                'name' => (string) $programme->name,
            ],
            'aav_aat_contribution_matrix' => $matrix,
        ]);
    }

    public function getAnalysisAatsWithMaxContributionBelow(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'n' => 'nullable|integer|min:0',
        ]);

        $universityId = (int) Auth::user()->university_id;
        $programId = (int) $validated['id'];
        $threshold = isset($validated['n']) ? max(0, (int) $validated['n']) : 1;
        $programme = $this->loadProgramForAnalysis($programId);

        ['semester_rows' => $semesterRows, 'all_ue_ids' => $allUeIds] =
            $this->loadSemesterUeEntriesForAnalysis($programId, $universityId);

        $matrix = $this->buildAavAatContributionMatrix($semesterRows, $allUeIds, $programId, $universityId);
        $aatMaxValues = $this->computeAatMaxContributionValues($matrix);

        $aatList = collect($matrix['aats'] ?? [])
            ->filter(function ($aat) use ($aatMaxValues, $threshold) {
                $aatId = (int) ($aat['id'] ?? 0);
                return ($aatMaxValues[$aatId] ?? 0) < $threshold;
            })
            ->map(function ($aat) use ($aatMaxValues) {
                $aatId = (int) ($aat['id'] ?? 0);
                return [
                    'id' => $aatId,
                    'code' => (string) ($aat['code'] ?? ''),
                    'name' => (string) ($aat['name'] ?? ''),
                    'max_contribution' => (int) ($aatMaxValues[$aatId] ?? 0),
                ];
            })
            ->values()
            ->all();

        return response()->json([
            'program' => [
                'id' => (int) $programme->id,
                'code' => (string) $programme->code,
                'name' => (string) $programme->name,
            ],
            'threshold' => $threshold,
            'aats_with_max_contribution_below' => $aatList,
        ]);
    }

    public function getAnalysisContributionIncoherences(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
            'ue_id' => 'nullable|integer|min:1',
        ]);

        $universityId = (int) Auth::user()->university_id;
        $programId = (int) $validated['id'];
        $selectedUeId = isset($validated['ue_id']) ? (int) $validated['ue_id'] : null;
        $programme = $this->loadProgramForAnalysis($programId);

        ['semester_rows' => $semesterRows] =
            $this->loadSemesterUeEntriesForAnalysis($programId, $universityId);

        [
            'ue_options' => $ueOptions,
            'incoherences' => $incoherences,
        ] = $this->buildUeContributionIncoherences($semesterRows, $programId, $universityId);

        $selectedUeIncoherence = null;
        if ($selectedUeId !== null) {
            $selectedUeIncoherence = collect($incoherences)
                ->first(fn($item) => (int) ($item['ue_id'] ?? 0) === $selectedUeId);
        }

        return response()->json([
            'program' => [
                'id' => (int) $programme->id,
                'code' => (string) $programme->code,
                'name' => (string) $programme->name,
            ],
            'ue_options' => $ueOptions,
            'all_ues_incoherences' => $incoherences,
            'selected_ue_id' => $selectedUeId,
            'selected_ue_incoherence' => $selectedUeIncoherence,
        ]);
    }

    public function getUEBySemester($programmeId, $proSemesterId)
    {
        return UniteEnseignement::select(
            'unite_enseignement.id',
            'unite_enseignement.code',
            'unite_enseignement.name',
            'unite_enseignement.ects',
            'ue_programme.display_order'
        )
            ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('ue_programme.fk_programme', $programmeId)
            ->where('ue_programme.fk_semester', $proSemesterId) // ✅ CORRECTION
            ->whereNotIn('unite_enseignement.id', function ($query) {
                $query->select('fk_ue_child')->from('element_constitutif');
            })
            ->orderBy('ue_programme.display_order')
            ->orderBy('ue_programme.id')
            ->with('children')
            ->get();
    }

    public function reorderUES(Request $request)
    {
        $validated = $request->validate([
            'programme_id' => 'required|integer|exists:programme,id',
            'semester_id' => 'required|integer|exists:pro_semester,id',
            'ue_id' => 'required|integer|exists:unite_enseignement,id',
            'direction' => 'required|in:up,down',
        ]);

        $universityId = Auth::user()->university_id;

        $semester = DB::table('pro_semester')
            ->where('id', $validated['semester_id'])
            ->where('fk_programme', $validated['programme_id'])
            ->where('university_id', $universityId)
            ->first();

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable pour ce programme.',
            ], 404);
        }

        return DB::transaction(function () use ($validated, $universityId) {
            $rows = $this->normalizeSemesterOrder(
                (int) $validated['programme_id'],
                (int) $validated['semester_id'],
                (int) $universityId
            );

            $index = $rows->search(function ($row) use ($validated) {
                return (int) $row->fk_unite_enseignement === (int) $validated['ue_id'];
            });

            if ($index === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'UE introuvable dans ce semestre.',
                ], 404);
            }

            $neighborIndex = $validated['direction'] === 'up'
                ? $index - 1
                : $index + 1;

            if ($neighborIndex < 0 || $neighborIndex >= $rows->count()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Aucun changement d’ordre.',
                ]);
            }

            $currentRow = $rows[$index];
            $neighborRow = $rows[$neighborIndex];

            DB::table('ue_programme')
                ->where('id', $currentRow->id)
                ->update(['display_order' => $neighborRow->display_order, 'updated_at' => now()]);

            DB::table('ue_programme')
                ->where('id', $neighborRow->id)
                ->update(['display_order' => $currentRow->display_order, 'updated_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Ordre mis à jour avec succès.',
            ]);
        });
    }

    public function unlinkUE(Request $request)
    {
        $validated = $request->validate([
            'programme_id' => 'required|integer|exists:programme,id',
            'semester_id' => 'required|integer|exists:pro_semester,id',
            'ue_id' => 'required|integer|exists:unite_enseignement,id',
        ]);

        $universityId = Auth::user()->university_id;

        $semester = DB::table('pro_semester')
            ->where('id', $validated['semester_id'])
            ->where('fk_programme', $validated['programme_id'])
            ->where('university_id', $universityId)
            ->first();

        if (!$semester) {
            return response()->json([
                'success' => false,
                'message' => 'Semestre introuvable pour ce programme.',
            ], 404);
        }

        return DB::transaction(function () use ($validated, $universityId) {
            $deleted = DB::table('ue_programme')
                ->where('fk_programme', $validated['programme_id'])
                ->where('fk_semester', $validated['semester_id'])
                ->where('fk_unite_enseignement', $validated['ue_id'])
                ->where('university_id', $universityId)
                ->delete();

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    'message' => "L'UE n'est pas liée à ce semestre.",
                ], 404);
            }

            $this->normalizeSemesterOrder(
                (int) $validated['programme_id'],
                (int) $validated['semester_id'],
                (int) $universityId
            );

            $this->ueAnomalyService->recomputeForUE((int) $validated['ue_id']);
            $this->ueAnomalyService->recomputeEmptySemesterAnomaliesForUniversity((int) $universityId);

            return response()->json([
                'success' => true,
                'message' => "UE retirée du semestre avec succès.",
            ]);
        });
    }

    private function normalizeSemesterOrder(int $programmeId, int $semesterId, int $universityId)
    {
        $rows = DB::table('ue_programme')
            ->select('id', 'fk_unite_enseignement', 'display_order')
            ->where('fk_programme', $programmeId)
            ->where('fk_semester', $semesterId)
            ->where('university_id', $universityId)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->values();

        foreach ($rows as $i => $row) {
            $expected = $i + 1;
            if ((int) $row->display_order !== $expected) {
                DB::table('ue_programme')
                    ->where('id', $row->id)
                    ->update(['display_order' => $expected, 'updated_at' => now()]);
                $row->display_order = $expected;
            }
        }

        return $rows;
    }

    private function attachAnomalySummaryToUes($ues, int $universityId, bool $includeChildren = false): void
    {
        $collection = collect($ues);
        if ($collection->isEmpty()) {
            return;
        }

        $ids = $collection->pluck('id')->map(fn($id) => (int) $id);
        if ($includeChildren) {
            $childIds = $collection
                ->flatMap(fn($ue) => collect($ue->children ?? [])->pluck('id'))
                ->map(fn($id) => (int) $id);
            $ids = $ids->merge($childIds);
        }

        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds(
            $ids->unique()->values()->all(),
            $universityId
        );

        foreach ($collection as $ue) {
            $ue->anomaly_summary = $summaryMap->get((int) $ue->id, [
                'has_anomaly' => false,
                'count' => 0,
                'severity' => 'info',
            ]);

            if ($includeChildren && !empty($ue->children)) {
                foreach ($ue->children as $child) {
                    $child->anomaly_summary = $summaryMap->get((int) $child->id, [
                        'has_anomaly' => false,
                        'count' => 0,
                        'severity' => 'info',
                    ]);
                }
            }
        }
    }

    private function loadProgramForAnalysis(int $programId)
    {
        return Programme::select('id', 'code', 'name')
            ->where('id', $programId)
            ->firstOrFail();
    }

    private function loadSemesterUeEntriesForAnalysis(int $programId, int $universityId): array
    {
        $semesters = DB::table('pro_semester')
            ->select('id', 'semester')
            ->where('fk_programme', $programId)
            ->where('university_id', $universityId)
            ->orderBy('semester')
            ->get();

        $semesterRows = [];
        $allUeIds = [];
        foreach ($semesters as $semester) {
            $ues = $this->getUEBySemester($programId, (int) $semester->id);
            $ueEntries = collect($ues)
                ->flatMap(function ($ue) {
                    $items = collect([[
                        'id' => (int) $ue->id,
                        'code' => (string) $ue->code,
                        'name' => (string) $ue->name,
                    ]]);

                    $children = collect($ue->children ?? [])
                        ->map(fn($child) => [
                            'id' => (int) $child->id,
                            'code' => (string) $child->code,
                            'name' => (string) $child->name,
                        ]);

                    return $items->merge($children);
                })
                ->unique('id')
                ->values()
                ->all();

            $semesterRows[] = [
                'id' => (int) $semester->id,
                'number' => (int) $semester->semester,
                'ues' => $ueEntries,
            ];

            $allUeIds = array_merge(
                $allUeIds,
                collect($ueEntries)->pluck('id')->map(fn($id) => (int) $id)->all()
            );
        }

        return [
            'semester_rows' => $semesterRows,
            'all_ue_ids' => collect($allUeIds)->unique()->values()->all(),
        ];
    }

    private function loadAnomalyDataForUes(array $ueIds, int $universityId): array
    {
        $anomalyRows = DB::table('anomalies')
            ->select('ue_id', 'code')
            ->where('university_id', $universityId)
            ->where('is_resolved', false)
            ->where('code', '!=', UEAnomalyService::CODE_HAS_ANOMALY)
            ->whereIn('ue_id', empty($ueIds) ? [-1] : $ueIds)
            ->get();

        $anomalyCodesByUe = $anomalyRows
            ->groupBy('ue_id')
            ->map(fn($group) => collect($group)->pluck('code')->unique()->values()->all())
            ->all();

        $availableAnomalyCodes = collect($anomalyRows)
            ->pluck('code')
            ->unique()
            ->sort()
            ->values()
            ->map(fn($code) => [
                'code' => $code,
                'label' => $this->anomalyCodeLabel((string) $code),
            ])
            ->all();

        return [
            'anomaly_codes_by_ue' => $anomalyCodesByUe,
            'available_anomaly_codes' => $availableAnomalyCodes,
        ];
    }

    private function buildAavAatContributionMatrix(array $semesterRows, array $allUeIds, int $programId, int $universityId): array
    {
        $semesterByUeId = [];
        foreach ($semesterRows as $semesterRow) {
            foreach ($semesterRow['ues'] as $ue) {
                $ueId = (int) ($ue['id'] ?? 0);
                if ($ueId > 0 && !array_key_exists($ueId, $semesterByUeId)) {
                    $semesterByUeId[$ueId] = (int) $semesterRow['number'];
                }
            }
        }

        $aatRows = DB::table('acquis_apprentissage_terminaux')
            ->select('id', 'code', 'name', 'level_contribution')
            ->where('university_id', $universityId)
            ->where(function ($query) use ($programId, $universityId) {
                $query->whereExists(function ($sub) use ($programId, $universityId) {
                    $sub->select(DB::raw(1))
                        ->from('ue_aat')
                        ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'ue_aat.fk_ue')
                        ->whereColumn('ue_aat.fk_aat', 'acquis_apprentissage_terminaux.id')
                        ->where('ue_aat.university_id', $universityId)
                        ->where('ue_programme.university_id', $universityId)
                        ->where('ue_programme.fk_programme', $programId);
                })->orWhereExists(function ($sub) use ($programId, $universityId) {
                    $sub->select(DB::raw(1))
                        ->from('aav_aat')
                        ->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'aav_aat.fk_aav')
                        ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'aavue_vise.fk_unite_enseignement')
                        ->whereColumn('aav_aat.fk_aat', 'acquis_apprentissage_terminaux.id')
                        ->where('aav_aat.university_id', $universityId)
                        ->where('aavue_vise.university_id', $universityId)
                        ->where('ue_programme.university_id', $universityId)
                        ->where('ue_programme.fk_programme', $programId)
                        ->where(function ($nested) use ($programId) {
                            $nested->whereNull('aav_aat.fk_programme')
                                ->orWhere('aav_aat.fk_programme', $programId);
                        });
                });
            })
            ->orderBy('code')
            ->get()
            ->map(fn($aat) => [
                'id' => (int) $aat->id,
                'code' => (string) $aat->code,
                'name' => (string) $aat->name,
                'level_contribution' => $aat->level_contribution !== null ? (int) $aat->level_contribution : null,
            ])
            ->values()
            ->all();

        $aavRows = DB::table('aavue_vise as av')
            ->join('acquis_apprentissage_vise as aav', 'aav.id', '=', 'av.fk_acquis_apprentissage_vise')
            ->join('unite_enseignement as ue', 'ue.id', '=', 'av.fk_unite_enseignement')
            ->where('av.university_id', $universityId)
            ->whereIn('av.fk_unite_enseignement', empty($allUeIds) ? [-1] : $allUeIds)
            ->select(
                'av.fk_unite_enseignement as ue_id',
                'ue.code as ue_code',
                'ue.name as ue_name',
                'aav.id as aav_id',
                'aav.code as aav_code',
                'aav.name as aav_name'
            )
            ->orderBy('ue.code')
            ->orderBy('aav.code')
            ->get();

        $aatIds = collect($aatRows)->pluck('id')->map(fn($id) => (int) $id)->all();
        $aavIds = $aavRows->pluck('aav_id')->map(fn($id) => (int) $id)->unique()->values()->all();

        $contributionRows = DB::table('aav_aat')
            ->select('fk_aav', 'fk_aat', 'fk_programme', 'contribution')
            ->where('university_id', $universityId)
            ->whereIn('fk_aav', empty($aavIds) ? [-1] : $aavIds)
            ->whereIn('fk_aat', empty($aatIds) ? [-1] : $aatIds)
            ->where(function ($query) use ($programId) {
                $query->whereNull('fk_programme')
                    ->orWhere('fk_programme', $programId);
            })
            ->orderByRaw('CASE WHEN fk_programme IS NULL THEN 0 ELSE 1 END DESC')
            ->get();

        $contributionByAav = [];
        foreach ($contributionRows as $row) {
            $aavId = (int) $row->fk_aav;
            $aatId = (int) $row->fk_aat;
            if (!isset($contributionByAav[$aavId])) {
                $contributionByAav[$aavId] = [];
            }
            if (array_key_exists($aatId, $contributionByAav[$aavId])) {
                continue;
            }
            $contributionByAav[$aavId][$aatId] = $row->contribution !== null ? (int) $row->contribution : null;
        }

        $matrixRows = $aavRows
            ->map(function ($row) use ($semesterByUeId, $aatIds, $contributionByAav) {
                $ueId = (int) $row->ue_id;
                $aavId = (int) $row->aav_id;
                $contrib = [];

                foreach ($aatIds as $aatId) {
                    $contrib[(int) $aatId] = $contributionByAav[$aavId][(int) $aatId] ?? null;
                }

                return [
                    'semester_number' => $semesterByUeId[$ueId] ?? null,
                    'ue_id' => $ueId,
                    'ue_code' => (string) $row->ue_code,
                    'ue_name' => (string) $row->ue_name,
                    'aav_id' => $aavId,
                    'aav_code' => (string) $row->aav_code,
                    'aav_name' => (string) $row->aav_name,
                    'contributions' => $contrib,
                ];
            })
            ->sortBy([
                ['semester_number', 'asc'],
                ['ue_code', 'asc'],
                ['aav_code', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'aats' => $aatRows,
            'rows' => $matrixRows,
        ];
    }

    private function computeAatMaxContributionValues(array $matrix): array
    {
        $values = [];
        $aats = collect($matrix['aats'] ?? []);
        foreach ($aats as $aat) {
            $values[(int) ($aat['id'] ?? 0)] = 0;
        }

        $rows = collect($matrix['rows'] ?? []);
        foreach ($rows as $row) {
            $contributions = $row['contributions'] ?? [];
            foreach ($contributions as $aatId => $value) {
                if ($value === null) {
                    continue;
                }

                $id = (int) $aatId;
                $numericValue = (int) $value;
                if (!array_key_exists($id, $values) || $numericValue > $values[$id]) {
                    $values[$id] = $numericValue;
                }
            }
        }

        return $values;
    }

    private function buildUeContributionIncoherences(array $semesterRows, int $programId, int $universityId): array
    {
        $ueMetaById = [];
        foreach ($semesterRows as $semesterRow) {
            $semesterNumber = isset($semesterRow['number']) ? (int) $semesterRow['number'] : null;
            foreach (($semesterRow['ues'] ?? []) as $ue) {
                $ueId = (int) ($ue['id'] ?? 0);
                if ($ueId <= 0 || isset($ueMetaById[$ueId])) {
                    continue;
                }
                $ueMetaById[$ueId] = [
                    'id' => $ueId,
                    'code' => (string) ($ue['code'] ?? ''),
                    'name' => (string) ($ue['name'] ?? ''),
                    'semester_number' => $semesterNumber,
                ];
            }
        }

        $ueIds = array_values(array_map('intval', array_keys($ueMetaById)));
        if (empty($ueIds)) {
            return [
                'ue_options' => [],
                'incoherences' => [],
            ];
        }

        $ueAatByUe = [];
        $ueAatRows = DB::table('ue_aat')
            ->select('fk_ue', 'fk_aat')
            ->where('university_id', $universityId)
            ->whereIn('fk_ue', $ueIds)
            ->get();
        foreach ($ueAatRows as $row) {
            $ueId = (int) $row->fk_ue;
            $aatId = (int) $row->fk_aat;
            if (!isset($ueAatByUe[$ueId])) {
                $ueAatByUe[$ueId] = [];
            }
            $ueAatByUe[$ueId][$aatId] = true;
        }

        $ueAavByUe = [];
        $allAavIdsLookup = [];
        $ueAavRows = DB::table('aavue_vise')
            ->select('fk_unite_enseignement', 'fk_acquis_apprentissage_vise')
            ->where('university_id', $universityId)
            ->whereIn('fk_unite_enseignement', $ueIds)
            ->get();
        foreach ($ueAavRows as $row) {
            $ueId = (int) $row->fk_unite_enseignement;
            $aavId = (int) $row->fk_acquis_apprentissage_vise;
            if (!isset($ueAavByUe[$ueId])) {
                $ueAavByUe[$ueId] = [];
            }
            $ueAavByUe[$ueId][$aavId] = true;
            $allAavIdsLookup[$aavId] = true;
        }
        $allAavIds = array_values(array_map('intval', array_keys($allAavIdsLookup)));

        $aavAatByAav = [];
        $allAatIdsLookup = [];
        if (!empty($allAavIds)) {
            $aavAatRows = DB::table('aav_aat')
                ->select('fk_aav', 'fk_aat', 'fk_programme')
                ->where('university_id', $universityId)
                ->whereIn('fk_aav', $allAavIds)
                ->where(function ($query) use ($programId) {
                    $query->whereNull('fk_programme')
                        ->orWhere('fk_programme', $programId);
                })
                ->orderByRaw('CASE WHEN fk_programme IS NULL THEN 0 ELSE 1 END DESC')
                ->get();

            foreach ($aavAatRows as $row) {
                $aavId = (int) $row->fk_aav;
                $aatId = (int) $row->fk_aat;
                if (!isset($aavAatByAav[$aavId])) {
                    $aavAatByAav[$aavId] = [];
                }
                $aavAatByAav[$aavId][$aatId] = true;
                $allAatIdsLookup[$aatId] = true;
            }
        }

        foreach ($ueAatByUe as $aatLookup) {
            foreach (array_keys($aatLookup) as $aatId) {
                $allAatIdsLookup[(int) $aatId] = true;
            }
        }
        $allAatIds = array_values(array_map('intval', array_keys($allAatIdsLookup)));

        $aavRowsById = DB::table('acquis_apprentissage_vise')
            ->select('id', 'code', 'name')
            ->where('university_id', $universityId)
            ->whereIn('id', empty($allAavIds) ? [-1] : $allAavIds)
            ->get()
            ->keyBy('id');

        $aatRowsById = DB::table('acquis_apprentissage_terminaux')
            ->select('id', 'code', 'name')
            ->where('university_id', $universityId)
            ->whereIn('id', empty($allAatIds) ? [-1] : $allAatIds)
            ->get()
            ->keyBy('id');

        $incoherences = [];
        foreach ($ueIds as $ueId) {
            $ueAatLookup = $ueAatByUe[$ueId] ?? [];
            $ueAatIds = array_map('intval', array_keys($ueAatLookup));
            $ueAavIds = array_map('intval', array_keys($ueAavByUe[$ueId] ?? []));

            $aatFromAavLookup = [];
            foreach ($ueAavIds as $aavId) {
                foreach (array_keys($aavAatByAav[$aavId] ?? []) as $aatId) {
                    $aatFromAavLookup[(int) $aatId] = true;
                }
            }

            $declaredUeButNoAavCases = [];
            foreach ($ueAatIds as $aatId) {
                if (isset($aatFromAavLookup[$aatId])) {
                    continue;
                }
                $declaredUeButNoAavCases[] = [
                    'aat_id' => $aatId,
                    'aat_code' => $aatRowsById->get($aatId)->code ?? null,
                    'aat_name' => $aatRowsById->get($aatId)->name ?? null,
                ];
            }

            $aavToAatNotDeclaredInUeCases = [];
            $seenPairs = [];
            foreach ($ueAavIds as $aavId) {
                foreach (array_keys($aavAatByAav[$aavId] ?? []) as $aatIdRaw) {
                    $aatId = (int) $aatIdRaw;
                    if (isset($ueAatLookup[$aatId])) {
                        continue;
                    }
                    $pairKey = $aavId . '|' . $aatId;
                    if (isset($seenPairs[$pairKey])) {
                        continue;
                    }
                    $seenPairs[$pairKey] = true;
                    $aavToAatNotDeclaredInUeCases[] = [
                        'aav_id' => $aavId,
                        'aav_code' => $aavRowsById->get($aavId)->code ?? null,
                        'aav_name' => $aavRowsById->get($aavId)->name ?? null,
                        'aat_id' => $aatId,
                        'aat_code' => $aatRowsById->get($aatId)->code ?? null,
                        'aat_name' => $aatRowsById->get($aatId)->name ?? null,
                    ];
                }
            }

            $totalCases = count($declaredUeButNoAavCases) + count($aavToAatNotDeclaredInUeCases);
            if ($totalCases === 0) {
                continue;
            }

            $meta = $ueMetaById[$ueId] ?? ['code' => '', 'name' => '', 'semester_number' => null];
            $incoherences[] = [
                'ue_id' => $ueId,
                'ue_code' => (string) ($meta['code'] ?? ''),
                'ue_name' => (string) ($meta['name'] ?? ''),
                'semester_number' => $meta['semester_number'] !== null ? (int) $meta['semester_number'] : null,
                'total_cases' => $totalCases,
                'declared_ue_but_no_aav_cases' => array_values($declaredUeButNoAavCases),
                'aav_to_aat_not_declared_in_ue_cases' => array_values($aavToAatNotDeclaredInUeCases),
            ];
        }

        $ueOptions = collect($ueMetaById)
            ->map(fn($ue) => [
                'id' => (int) $ue['id'],
                'code' => (string) ($ue['code'] ?? ''),
                'name' => (string) ($ue['name'] ?? ''),
                'semester_number' => $ue['semester_number'] !== null ? (int) $ue['semester_number'] : null,
            ])
            ->sortBy('code', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        $incoherences = collect($incoherences)
            ->sortBy([
                ['semester_number', 'asc'],
                ['ue_code', 'asc'],
            ])
            ->values()
            ->all();

        return [
            'ue_options' => $ueOptions,
            'incoherences' => $incoherences,
        ];
    }


    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('code', 'id', 'name', 'ects')
            ->where('id', $validated['id'])
            ->first();
        $response->semester = Pro_semester::select('ects', 'semester')
            ->where(
                'fk_programme',
                $validated['id']
            )->get();
        return $response;
    }

    private function anomalyCodeLabel(string $code): string
    {
        return match ($code) {
            UEAnomalyService::CODE_PREREQ_AS_UE => 'Erreur de prérequis (UE)',
            UEAnomalyService::CODE_PREREQ_OUTSIDE_ALLOWED => 'Erreur de prérequis (les prérequis ne sont pas des AAV d un semestre précédent)',
            UEAnomalyService::CODE_EMPTY_AAV_LIST => 'Erreur de données (liste des AAV vide)',
            UEAnomalyService::CODE_EMPTY_CREDITS => 'Erreur de crédit',
            UEAnomalyService::CODE_MISSING_SEMESTER => "Erreur d'affectation de semestre",
            UEAnomalyService::CODE_MISSING_AAV_AAT_CONTRIBUTION => 'Erreur de contribution',
            UEAnomalyService::CODE_MISSING_AAT_LEVEL => 'Erreur de niveau de contribution',
            UEAnomalyService::CODE_INCOHERENT_AAT_CONTRIBUTION => 'Erreur de cohérence de contribution (les AAV ne contribuent pas à un AAT de l`UE)',
            default => $code,
        };
    }
}

