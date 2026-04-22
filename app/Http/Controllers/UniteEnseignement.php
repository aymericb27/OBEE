<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;
use App\Models\AcquisApprentissageTerminaux;
use App\Models\ElementConstitutif;
use App\Models\Programme;
use App\Services\CodeGeneratorService;
use App\Services\UEAnomalyService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UniteEnseignement extends Controller
{
    public function __construct(
        private CodeGeneratorService $codeGen,
        private UEAnomalyService $ueAnomalyService
    ) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'ects' => 'nullable|integer|max:200',
            'description' => 'nullable|string|max:2024',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'ueprerequis' => ['array'],
            'ueprerequis.*.id' => ['integer', 'exists:unite_enseignement,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['required', 'array', 'min:1'],
            'pro.*.id' => ['required', 'integer', 'exists:programme,id'],
            'pro.*.semester' => ['nullable', 'integer', 'min:1'],
            'aat' => ['array'],
            'aat.*.id' => ['nullable', 'integer', 'exists:acquis_apprentissage_terminaux,id'],
            'aat.*.contribution' => ['nullable', 'integer', 'min:1', 'max:3'],
            'ueParentID' => ["nullable", 'integer', 'exists:unite_enseignement,id'],
            'ueParentContribution' => ["nullable", 'integer', 'min:1', 'max:3']
        ], [
            'pro.required' => "Au moins un programme est obligatoire pour créer une UE.",
            'pro.array' => "La liste des programmes est invalide.",
            'pro.min' => "Au moins un programme est obligatoire pour créer une UE.",
            'pro.*.id.required' => "Chaque programme sélectionné doit avoir un identifiant valide.",
        ]);

        // ✅ si vide => générer
        if (empty($validated['code'])) {
            $validated['code'] = $this->codeGen->nextUE();
        }

        try {
            $ue = UE::create([
                'name' => $validated['name'],
                'ects' => $validated['ects'],
                'code' => $validated['code'],
                'description' => $validated['description'],
                'university_id' => Auth::user()->university_id,

            ]);
        } catch (QueryException $e) {
            // duplicate key
            if (($e->errorInfo[0] ?? null) === '23000') {
                $code = $validated['code'];
                throw ValidationException::withMessages([
                    'main.code' => ["Le sigle d’UE \"$code\" existe déjà dans le logiciel, veuillez en fournir un différent."]
                ]);
            }
            throw $e;
        }


        // ✅ Mise à jour des relations (si tu as des tables pivots)
        if (isset($validated['aavvise'])) {
            $pivotData = [];
            foreach ($validated['aavvise'] as $item) {
                $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
            }
            $ue->aavvise()->sync($pivotData);
        }

        if (isset($validated['aavprerequis'])) {
            $pivotData = [];
            foreach ($validated['aavprerequis'] as $item) {
                $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
            }
            $ue->prerequis()->sync($pivotData);
        }

        if (isset($validated['ueprerequis'])) {
            $pivotData = [];
            foreach ($validated['ueprerequis'] as $item) {
                $prereqUeId = (int) $item['id'];
                if ($prereqUeId === (int) $ue->id) {
                    continue;
                }
                $pivotData[$prereqUeId] = ['university_id' => Auth::user()->university_id];
            }
            $ue->uePrerequis()->sync($pivotData);
        }

        if (!empty($validated['aat'])) {

            $pivotData = [];
            foreach ($validated['aat'] as $item) {
                $pivotData[$item['id']] = ['contribution' => $item['contribution'], 'university_id' => Auth::user()->university_id];
            }

            // ajoute tous les liens pivot d'un coup
            $ue->aat()->attach($pivotData);
        }
        if (!empty($validated['pro'])) {

            $universityId = Auth::user()->university_id;

            // 1) on prépare un mapping (programme_id + semester) -> pro_semester.id
            $programmeIds = collect($validated['pro'])->pluck('id')->unique()->values();

            $proSemesters = DB::table('pro_semester')
                ->whereIn('fk_programme', $programmeIds)
                ->where('university_id', $universityId)
                ->get(['id', 'fk_programme', 'semester']);

            // clé "programmeId-semester" => pro_semester.id
            $map = [];
            foreach ($proSemesters as $ps) {
                $map[$ps->fk_programme . '-' . $ps->semester] = $ps->id;
            }

            // 2) construire les lignes pivot ue_programme
            $rows = [];

            foreach ($validated['pro'] as $item) {
                $programmeId = (int) $item['id'];
                $semesterNumber = isset($item['semester']) && $item['semester'] !== null && $item['semester'] !== ''
                    ? (int) $item['semester']
                    : null;

                $semesterId = null;
                if ($semesterNumber !== null) {
                    $key = $programmeId . '-' . $semesterNumber;
                    if (!isset($map[$key])) {
                        // semestre invalide pour ce programme
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'pro' => ["Semestre $semesterNumber introuvable pour le programme ID $programmeId."]
                        ]);
                    }
                    $semesterId = (int) $map[$key];
                }

                $rows[] = [
                    'fk_unite_enseignement' => $ue->id,
                    'fk_programme' => $programmeId,
                    'fk_semester' => $semesterId,
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 3) insert sans casser sur doublon (contrainte unique)
            DB::table('ue_programme')->insertOrIgnore($rows);
        }

        if (!empty($validated['ueParentID'])) {
            ElementConstitutif::create([
                'fk_ue_parent' => $validated['ueParentID'],
                'fk_ue_child' => $ue->id,
                'contribution' => $validated['ueParentContribution'],
                'university_id' => Auth::user()->university_id,

            ]);
        }
        $this->ueAnomalyService->recomputeForUE((int) $ue->id);
        return response()->json([
            'success' => true,
            'id' => $ue->id,
            'message' => "Unité d'enseignement créé avec succès.",
        ]);
    }

    public function update(Request $request)
    {
        // ✅ Validation
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:unite_enseignement,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ects' => ['nullable', 'integer'],
            'code' => 'required|string|max:50',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'ueprerequis' => ['array'],
            'ueprerequis.*.id' => ['integer', 'exists:unite_enseignement,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['required', 'array', 'min:1'],
            'pro.*.id' => ['required', 'integer', 'exists:programme,id'],
            'pro.*.semester' => ['nullable', 'integer', 'min:1'],
            'aat' => ['array'],
            'aat.*.id' => ['nullable', 'integer', 'exists:acquis_apprentissage_terminaux,id'],
            'aat.*.contribution' => ['nullable', 'integer', 'min:1', 'max:3'],
            'ueParentID' => ["nullable", 'integer', 'exists:unite_enseignement,id'],
            'ueParentContribution' => ["nullable", 'integer', 'min:1', 'max:3']
        ], [
            'pro.required' => "Au moins un programme est obligatoire pour modifier une UE.",
            'pro.array' => "La liste des programmes est invalide.",
            'pro.min' => "Au moins un programme est obligatoire pour modifier une UE.",
            'pro.*.id.required' => "Chaque programme sélectionné doit avoir un identifiant valide.",
        ]);

        // ✅ Récupération de l’UE
        $ue = UE::findOrFail($validated['id']);

        try {
            // ✅ Mise à jour des champs de base
            $ue->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? '',
                'ects' => $validated['ects'],
                'code' => $validated['code'],
                'university_id' => Auth::user()->university_id,
            ]);
        } catch (QueryException $e) {
            // duplicate key
            if (($e->errorInfo[0] ?? null) === '23000') {
                $code = $validated['code'];
                throw ValidationException::withMessages([
                    'main.code' => ["Le sigle d’UE \"$code\" existe déjà dans le logiciel, veuillez en fournir un différent."]
                ]);
            }
            throw $e;
        }
        // ✅ Mise à jour des relations (si tu as des tables pivots)
        if (isset($validated['aavvise'])) {
            $pivotData = [];
            foreach ($validated['aavvise'] as $item) {
                $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
            }
            $ue->aavvise()->sync($pivotData);
        }

        if (isset($validated['aavprerequis'])) {
            $pivotData = [];
            foreach ($validated['aavprerequis'] as $item) {
                $pivotData[$item['id']] = ['university_id' => Auth::user()->university_id];
            }
            $ue->prerequis()->sync($pivotData);
        }
        if (isset($validated['ueprerequis'])) {
            $pivotData = [];
            foreach ($validated['ueprerequis'] as $item) {
                $prereqUeId = (int) $item['id'];
                if ($prereqUeId === (int) $ue->id) {
                    continue;
                }
                $pivotData[$prereqUeId] = ['university_id' => Auth::user()->university_id];
            }
            $ue->uePrerequis()->sync($pivotData);
        }
        if (isset($validated['pro'])) {

            $universityId = Auth::user()->university_id;

            // 1) construire le mapping (programme_id + semester) -> pro_semester.id
            $programmeIds = collect($validated['pro'])->pluck('id')->unique()->values();

            $proSemesters = DB::table('pro_semester')
                ->whereIn('fk_programme', $programmeIds)
                ->where('university_id', $universityId)
                ->get(['id', 'fk_programme', 'semester']);

            $map = [];
            foreach ($proSemesters as $ps) {
                $map[$ps->fk_programme . '-' . $ps->semester] = $ps->id;
            }

            // 2) construire les lignes pivot ue_programme
            $rows = [];
            foreach ($validated['pro'] as $item) {
                $programmeId = (int) $item['id'];
                $semesterNumber = isset($item['semester']) && $item['semester'] !== null && $item['semester'] !== ''
                    ? (int) $item['semester']
                    : null;

                $semesterId = null;
                if ($semesterNumber !== null) {
                    $key = $programmeId . '-' . $semesterNumber;
                    if (!isset($map[$key])) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'pro' => ["Semestre $semesterNumber introuvable pour le programme ID $programmeId."]
                        ]);
                    }
                    $semesterId = (int) $map[$key];
                }

                $rows[] = [
                    'fk_unite_enseignement' => $ue->id,
                    'fk_programme' => $programmeId,
                    'fk_semester' => $semesterId,
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // 3) sync "manuel" (on remplace ce que l'UE avait)
            DB::table('ue_programme')
                ->where('fk_unite_enseignement', $ue->id)
                ->where('university_id', $universityId)
                ->delete();

            DB::table('ue_programme')->insert($rows);
        }

        if (isset($validated['aat'])) {

            $pivotData = [];
            foreach ($validated['aat'] as $item) {
                $pivotData[$item['id']] = ['contribution' => $item['contribution'], 'university_id' => Auth::user()->university_id];
            }

            // ajoute tous les liens pivot d'un coup
            $ue->aat()->sync($pivotData);
        }
        $this->ueAnomalyService->recomputeForUE((int) $ue->id);
        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement mise à jour avec succès.",
        ]);
    }

    public function getPro(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:unite_enseignement,id',
        ]);

        $response = Programme::select(
            'programme.id',
            'programme.name',
            'programme.code',
            'pro_semester.semester as semestre'
        )
            ->join('ue_programme', 'ue_programme.fk_programme', '=', 'programme.id')
            ->leftJoin('pro_semester', 'pro_semester.id', '=', 'ue_programme.fk_semester')
            ->where('ue_programme.fk_unite_enseignement', $validated['id'])
            ->get();

        return response()->json($response);
    }



    public function getAATs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AcquisApprentissageTerminaux::select('acquis_apprentissage_terminaux.id', 'name', 'code', 'contribution')
            ->join('ue_aat', 'fk_aat', '=', 'acquis_apprentissage_terminaux.id')
            ->where('fk_ue', $validated['id'])->get();
        return $response;
    }

    public function getAATContributedByAAVs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $ueId = (int) $validated['id'];
        $universityId = (int) Auth::user()->university_id;

        $ecIds = ElementConstitutif::where('fk_ue_parent', $ueId)
            ->pluck('fk_ue_child')
            ->toArray();
        $ueIds = array_unique(array_merge([$ueId], $ecIds));

        $aavIds = DB::table('aavue_vise')
            ->whereIn('fk_unite_enseignement', $ueIds)
            ->where('university_id', $universityId)
            ->pluck('fk_acquis_apprentissage_vise')
            ->unique()
            ->values();

        if ($aavIds->isEmpty()) {
            return response()->json([]);
        }

        $response = AcquisApprentissageTerminaux::query()
            ->select(
                'acquis_apprentissage_terminaux.id',
                'acquis_apprentissage_terminaux.code',
                'acquis_apprentissage_terminaux.name'
            )
            ->join('aav_aat', 'aav_aat.fk_aat', '=', 'acquis_apprentissage_terminaux.id')
            ->whereIn('aav_aat.fk_aav', $aavIds)
            ->where('aav_aat.university_id', $universityId)
            ->distinct()
            ->orderBy('acquis_apprentissage_terminaux.code')
            ->get();

        return response()->json($response);
    }
    public function addEC(Request $request)
    {
        $validated = $request->validate([
            'idParent' => 'required|integer',
            'listChild' => 'required|array',
        ]);

        foreach ($validated['listChild'] as $index => $EC) {
            ElementConstitutif::create([
                'fk_ue_parent' => $validated['idParent'],
                'fk_ue_child' => $EC['id'],
                'contribution' => 1
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement mise à jour avec succès.",
        ]);
    }

    public function getChildren(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $ues = UE::select('unite_enseignement.id', 'unite_enseignement.code', 'unite_enseignement.name', 'element_constitutif.contribution')
            ->join('element_constitutif', 'element_constitutif.fk_ue_child', '=', 'unite_enseignement.id')
            ->where('element_constitutif.fk_ue_parent', $validated['id'])
            ->orderBy('unite_enseignement.code')
            ->get();

        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds(
            $ues->pluck('id')->map(fn($id) => (int) $id)->all(),
            (int) Auth::user()->university_id
        );

        $ues->each(function ($ue) use ($summaryMap) {
            $ue->anomaly_summary = $summaryMap->get((int) $ue->id, [
                'has_anomaly' => false,
                'count' => 0,
                'severity' => 'info',
            ]);
        });

        return $ues;
    }

    public function getAAVvise(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $ueId = (int) $validated['id'];
        $universityId = (int) Auth::user()->university_id;

        // 1) IDs des UEs à prendre en compte : UE + ses éléments constitutifs
        // ⚠️ Adapte le nom des colonnes de la table element_constitutif si besoin
        // récupère les EC (UE enfants)
        $ecIds = ElementConstitutif::where('fk_ue_parent', $ueId)
            ->pluck('fk_ue_child')
            ->toArray();
        $ueIds = array_unique(array_merge([$ueId], $ecIds));

        // 2) AAV vise de toutes ces UEs
        $response = AAV::query()
            ->select(
                'acquis_apprentissage_vise.id as id',
                'acquis_apprentissage_vise.code as code',
                'acquis_apprentissage_vise.name as name',

                'ue.id as ue_source_id',
                'ue.code as ue_source_code',
                'ue.name as ue_source_name'
            )
            ->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->join('unite_enseignement as ue', 'ue.id', '=', 'aavue_vise.fk_unite_enseignement')
            ->whereIn('aavue_vise.fk_unite_enseignement', $ueIds)
            ->where('aavue_vise.university_id', $universityId)
            ->where('acquis_apprentissage_vise.university_id', $universityId)
            ->orderBy('ue.code')
            ->orderBy('acquis_apprentissage_vise.code')
            ->get();

        return $this->appendAATContributions($response);
    }

    public function getAAVviseOnlyParent(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('acquis_apprentissage_vise.id', 'name', 'code')
            ->join('aavue_vise', 'fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->where('aavue_vise.fk_unite_enseignement', $validated['id'])
            ->groupBy('acquis_apprentissage_vise.id', 'name', 'code')
            ->havingRaw('COUNT(aavue_vise.fk_unite_enseignement) = 1')
            ->get();

        return $response;
    }

    public function getAAVprerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ueId = (int) $validated['id'];
        $universityId = (int) Auth::user()->university_id;

        $response = AAV::select('acquis_apprentissage_vise.id', 'name', 'code')
            ->join('aavue_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')
            ->where('aavue_prerequis.fk_unite_enseignement', $ueId)
            ->where('aavue_prerequis.university_id', $universityId)
            ->where('acquis_apprentissage_vise.university_id', $universityId)
            ->get();

        return $this->appendAATContributions($response);
    }

    public function getUEprerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:unite_enseignement,id',
        ]);

        $response = UE::select(
            'unite_enseignement.id',
            'unite_enseignement.code',
            'unite_enseignement.name'
        )
            ->join('ue_prerequis', 'ue_prerequis.fk_UE_prerequis', '=', 'unite_enseignement.id')
            ->where('ue_prerequis.fk_UE_parent', (int) $validated['id'])
            ->where('ue_prerequis.university_id', (int) Auth::user()->university_id)
            ->orderBy('unite_enseignement.code')
            ->get();

        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds(
            $response->pluck('id')->map(fn($id) => (int) $id)->all(),
            (int) Auth::user()->university_id
        );

        $response->each(function ($ue) use ($summaryMap) {
            $ue->anomaly_summary = $summaryMap->get((int) $ue->id, [
                'has_anomaly' => false,
                'count' => 0,
                'severity' => 'info',
            ]);
        });

        return response()->json($response);
    }

    private function appendAATContributions($aavs)
    {
        $aavIds = $aavs->pluck('id')->unique()->values();
        $universityId = (int) Auth::user()->university_id;

        if ($aavIds->isEmpty()) {
            return $aavs->map(function ($aav) {
                $aav->aat_contributions = [];
                return $aav;
            });
        }

        $contributionsByAav = DB::table('aav_aat')
            ->join('acquis_apprentissage_terminaux as aat', 'aat.id', '=', 'aav_aat.fk_aat')
            ->whereIn('aav_aat.fk_aav', $aavIds)
            ->where('aav_aat.university_id', $universityId)
            ->select(
                'aav_aat.fk_aav as aav_id',
                'aat.id as aat_id',
                'aat.code as aat_code',
                'aat.level_contribution as aat_level_contribution',
                'aav_aat.contribution as contribution'
            )
            ->orderBy('aat.code')
            ->get()
            ->groupBy('aav_id');

        return $aavs->map(function ($aav) use ($contributionsByAav) {
            $aav->aat_contributions = collect($contributionsByAav->get($aav->id, []))
                ->map(fn($row) => [
                    'aat_id' => (int) $row->aat_id,
                    'aat_code' => $row->aat_code,
                    'aat_level_contribution' => (int) $row->aat_level_contribution,
                    'contribution' => (int) $row->contribution,
                ])
                ->values()
                ->all();

            return $aav;
        });
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = UE::where('unite_enseignement.id', $validated['id'])
            ->with(['parent', 'children'])
            ->first();

        if (!$response) {
            return $response;
        }

        $universityId = (int) Auth::user()->university_id;
        $allUeIds = collect([$response->id])
            ->merge(collect($response->children ?? [])->pluck('id'))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds($allUeIds, $universityId);
        $response->anomaly_summary = $summaryMap->get((int) $response->id, [
            'has_anomaly' => false,
            'count' => 0,
            'severity' => 'info',
        ]);
        $response->anomalies = $this->ueAnomalyService->getDetailsForUE((int) $response->id, $universityId);

        if (!empty($response->children)) {
            foreach ($response->children as $child) {
                $child->anomaly_summary = $summaryMap->get((int) $child->id, [
                    'has_anomaly' => false,
                    'count' => 0,
                    'severity' => 'info',
                ]);
            }
        }

        return $response;
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ue = UE::findOrFail($validated['id']);
        $universityId = (int) Auth::user()->university_id;
        $ueId = (int) $ue->id;
        $programIds = DB::table('ue_programme')
            ->where('university_id', $universityId)
            ->where('fk_unite_enseignement', $ueId)
            ->pluck('fk_programme')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $ue->delete();

        DB::table('anomalies')
            ->where('university_id', $universityId)
            ->where('ue_id', $ueId)
            ->delete();

        foreach ($programIds as $programId) {
            $this->ueAnomalyService->recomputeForProgram((int) $programId, $universityId);
        }

        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement supprimé avec succès.",
        ]);
    }

    public function get(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|integer|exists:programme,id',
        ]);
        /*         // Convertir onlyErrors en bool si présent
        if ($request->has('onlyErrors')) {
            $request->merge([
                'onlyErrors' => filter_var($request->onlyErrors, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validated = $request->validate([
            'program'    => 'nullable|integer|exists:programme,id',
        ]); */

        $ues = UE::select('unite_enseignement.id', 'code', 'name', 'ects');
        if (!empty($validated['program_id'])) {
            $ues->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('ue_programme.fk_programme', (int) $validated['program_id'])
                ->distinct();
        }

        /*         // ✔ programme filtré seulement si fourni
        if (!empty($validated['program'])) {
            $ues->join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('fk_programme', $validated['program']);
        }

        // ✔ semestre filtré seulement si fourni
        if (!empty($validated['semestre'])) {
            $ues->where('semestre', $validated['semestre']);
        } */

        $ues = $ues->get();
        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds(
            $ues->pluck('id')->map(fn($id) => (int) $id)->all(),
            (int) Auth::user()->university_id
        );
        $ues->each(function ($ue) use ($summaryMap) {
            $ue->anomaly_summary = $summaryMap->get((int) $ue->id, [
                'has_anomaly' => false,
                'count' => 0,
                'severity' => 'info',
            ]);
        });

        // Analyse d'erreurs
        /*  $EC = new ErrorController;
        $result = $EC->getErrorUES($ues, true);

        // ✔ onlyErrors appliqué uniquement si provided et true
        if (!empty($validated['onlyErrors'])) {

            $result = collect($result)
                ->filter(fn($ue) => isset($ue->error) && $ue->error === true)
                ->values();
        }
 */
        return $ues;
    }

    public function refreshAnomalies()
    {
        $universityId = (int) Auth::user()->university_id;
        $result = $this->ueAnomalyService->recomputeForUniversity($universityId);

        return response()->json([
            'success' => true,
            'message' => 'Anomalies recalculées avec succès.',
            'data' => $result,
        ]);
    }
}
