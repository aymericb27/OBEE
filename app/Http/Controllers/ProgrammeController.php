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
}

