<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\ElementConstitutif;
use App\Models\UniteEnseignement;
use App\Services\CodeGeneratorService;
use App\Services\UEAnomalyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcquisApprentissageTerminaux extends Controller
{
    public function __construct(
        private CodeGeneratorService $codeGen,
        private UEAnomalyService $ueAnomalyService
    ) {}

    public function get(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|integer|exists:programme,id',
        ]);

        $result = AAT::query()
            ->leftJoin('programme', 'programme.id', '=', 'acquis_apprentissage_terminaux.fk_programme')
            ->select(
                'acquis_apprentissage_terminaux.id',
                'acquis_apprentissage_terminaux.code',
                'acquis_apprentissage_terminaux.name',
                'acquis_apprentissage_terminaux.description',
                'acquis_apprentissage_terminaux.level_contribution',
                'acquis_apprentissage_terminaux.fk_programme',
                'programme.code as programme_code',
                'programme.name as programme_name',
            );

        if (!empty($validated['program_id'])) {
            $result->where('acquis_apprentissage_terminaux.fk_programme', (int) $validated['program_id']);
        }

        $result = $result->get();
        return $result;
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
        ]);
        $aatId = (int) $validated['id'];
        $universityId = (int) Auth::user()->university_id;

        $directUeIds = DB::table('ue_aat')
            ->where('university_id', $universityId)
            ->where('fk_aat', $aatId)
            ->pluck('fk_ue');

        $viaAavUeIds = DB::table('aav_aat as aa')
            ->join('aavue_vise as av', function ($join) use ($universityId) {
                $join->on('av.fk_acquis_apprentissage_vise', '=', 'aa.fk_aav')
                    ->where('av.university_id', '=', $universityId);
            })
            ->where('aa.university_id', $universityId)
            ->where('aa.fk_aat', $aatId)
            ->pluck('av.fk_unite_enseignement');

        $impactedUeIds = $directUeIds
            ->merge($viaAavUeIds)
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $aat = AAT::findOrFail($validated['id']);
        $aat->delete();

        $this->ueAnomalyService->recomputeForUEIds($impactedUeIds);

        return response()->json([
            'success' => true,
            'message' => "Acquis d'apprentissage terminal supprimé avec succès.",
        ]);
    }

    public function getTree(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
        ]);

        $aatId = (int) $validated['id'];

        $aat = AAT::findOrFail($aatId);

        // 1) Toutes les UEs liées directement à cet AAT
        $ues = UniteEnseignement::query()
            ->select('unite_enseignement.id', 'unite_enseignement.code', 'unite_enseignement.name', 'unite_enseignement.ects')
            ->whereHas('aat', function ($q) use ($aatId) {
                $q->where('acquis_apprentissage_terminaux.id', $aatId);
            })
            ->orderBy('unite_enseignement.code')
            ->get();

        // 2) Pour chaque UE, on garde les EC et les AAV (UE + EC) qui contribuent à l'AAT demandé
        $ues->each(function ($ue) use ($aatId) {
            $children = UniteEnseignement::query()
                ->select(
                    'unite_enseignement.id',
                    'unite_enseignement.code',
                    'unite_enseignement.name',
                    'element_constitutif.contribution'
                )
                ->join('element_constitutif', 'element_constitutif.fk_ue_child', '=', 'unite_enseignement.id')
                ->where('element_constitutif.fk_ue_parent', $ue->id)
                ->orderBy('unite_enseignement.code')
                ->get();

            $childIds = $children->pluck('id')->toArray();
            $ueIds = array_unique(array_merge([$ue->id], $childIds));

            // AAV venant de l'UE ou de ses EC, filtrés sur l'AAT demandé
            $aavs = AAV::query()
                ->select(
                    'acquis_apprentissage_vise.id as id',
                    'acquis_apprentissage_vise.code as code',
                    'acquis_apprentissage_vise.name as name',
                    'aav_aat.contribution as contribution',
                    'aat_target.fk_programme as fk_programme',
                    'programme.code as programme_code',
                    'programme.name as programme_name',
                    'ue_source.id as ue_source_id',
                    'ue_source.code as ue_source_code',
                    'ue_source.name as ue_source_name'
                )
                ->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
                ->join('aav_aat', 'aav_aat.fk_aav', '=', 'acquis_apprentissage_vise.id')
                ->join('acquis_apprentissage_terminaux as aat_target', 'aat_target.id', '=', 'aav_aat.fk_aat')
                ->leftJoin('programme', 'programme.id', '=', 'aat_target.fk_programme')
                ->join('unite_enseignement as ue_source', 'ue_source.id', '=', 'aavue_vise.fk_unite_enseignement')
                ->where('aav_aat.fk_aat', $aatId)
                ->whereIn('aavue_vise.fk_unite_enseignement', $ueIds)
                ->orderBy('ue_source.code')
                ->orderBy('acquis_apprentissage_vise.code')
                ->get();

            $aavs->each(function ($row) use ($ue) {
                $row->origine_type = ((int) $row->ue_source_id === (int) $ue->id) ? 'UE' : 'EC';
            });

            $ue->setAttribute('children', $children);
            $ue->setAttribute('aavvise', $aavs);
            $ue->setAttribute('has_aav_for_selected_aat', $aavs->isNotEmpty());
        });

        $allUeIds = $ues->pluck('id')
            ->merge(
                $ues->flatMap(fn($ue) => collect($ue->children ?? [])->pluck('id'))
            )
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds($allUeIds, (int) Auth::user()->university_id);
        $ues->each(function ($ue) use ($summaryMap) {
            $ue->anomaly_summary = $summaryMap->get((int) $ue->id, [
                'has_anomaly' => false,
                'count' => 0,
                'severity' => 'info',
            ]);

            foreach ($ue->children ?? [] as $child) {
                $child->anomaly_summary = $summaryMap->get((int) $child->id, [
                    'has_anomaly' => false,
                    'count' => 0,
                    'severity' => 'info',
                ]);
            }
        });

        $aat->setAttribute('ues', $ues);

        return $aat;
    }

    public function update(Request $request)
    {
        $universityId = Auth::user()->university_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:acquis_apprentissage_terminaux,id'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('acquis_apprentissage_terminaux', 'code')
                    ->where(fn($q) => $q->where('university_id', $universityId))
                    ->ignore($request->input('id')),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',
            'level_contribution' => 'required|integer|min:3|max:10',
            'fk_programme' => [
                'required',
                'integer',
                Rule::exists('programme', 'id')
                    ->where(fn($query) => $query->where('university_id', $universityId)),
            ],
        ]);

        /*
     |--------------------------------------------------------------------------
     | Récupération AAT
     |--------------------------------------------------------------------------
     */
        $aat = AAT::findOrFail($validated['id']);

        /*
     |--------------------------------------------------------------------------
     | Mise à jour AAT
     |--------------------------------------------------------------------------
     */
        $aat->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'level_contribution' => $validated['level_contribution'],
            'fk_programme' => (int) $validated['fk_programme'],
        ]);

        $this->ueAnomalyService->recomputeForAAT((int) $aat->id, $universityId);

        return response()->json([
            'success' => true,
            'id' => $aat->id,
            'message' => 'AAT mis à jour avec succès.',
        ]);
    }
    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAT::leftJoin('programme', 'programme.id', '=', 'acquis_apprentissage_terminaux.fk_programme')
            ->select(
                'acquis_apprentissage_terminaux.code',
                'acquis_apprentissage_terminaux.id',
                'acquis_apprentissage_terminaux.name',
                'acquis_apprentissage_terminaux.description',
                'acquis_apprentissage_terminaux.level_contribution',
                'acquis_apprentissage_terminaux.fk_programme',
                'programme.code as programme_code',
                'programme.name as programme_name',
            )
            ->where('acquis_apprentissage_terminaux.id', $validated['id'])
            ->first();

        return $response;
    }
    public function getAAVs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
        ]);

        $aat = AAT::select('id', 'level_contribution')->findOrFail($validated['id']);

        $rows = DB::table('aav_aat')
            ->join('acquis_apprentissage_vise', 'acquis_apprentissage_vise.id', '=', 'aav_aat.fk_aav')
            ->join('acquis_apprentissage_terminaux as aat_target', 'aat_target.id', '=', 'aav_aat.fk_aat')
            ->leftJoin('programme', 'programme.id', '=', 'aat_target.fk_programme')
            ->where('aav_aat.fk_aat', $validated['id'])
            ->select(
                'aav_aat.id as row_key',
                'acquis_apprentissage_vise.id',
                'acquis_apprentissage_vise.code',
                'acquis_apprentissage_vise.name',
                'aav_aat.contribution',
                'aat_target.fk_programme',
                'programme.code as programme_code',
                'programme.name as programme_name'
            )
            ->orderBy('acquis_apprentissage_vise.code')
            ->orderBy('programme.code')
            ->orderBy('aav_aat.id')
            ->get();

        $aavIds = $rows->pluck('id')->unique()->values();
        $ueRows = DB::table('aavue_vise')
            ->join('unite_enseignement', 'unite_enseignement.id', '=', 'aavue_vise.fk_unite_enseignement')
            ->whereIn('aavue_vise.fk_acquis_apprentissage_vise', $aavIds)
            ->select(
                'aavue_vise.fk_acquis_apprentissage_vise as aav_id',
                'unite_enseignement.id',
                'unite_enseignement.code',
                'unite_enseignement.name'
            )
            ->orderBy('unite_enseignement.code')
            ->get()
            ->groupBy('aav_id');

        $allUeIds = $ueRows->flatten(1)->pluck('id')->map(fn($id) => (int) $id)->unique()->values()->all();
        $summaryMap = $this->ueAnomalyService->getSummaryForUEIds($allUeIds, (int) Auth::user()->university_id);

        $aavs = $rows->map(function ($row) use ($aat, $ueRows, $summaryMap) {
            $ues = collect($ueRows->get($row->id, []))
                ->map(fn($ue) => [
                    'id' => $ue->id,
                    'code' => $ue->code,
                    'name' => $ue->name,
                    'anomaly_summary' => $summaryMap->get((int) $ue->id, [
                        'has_anomaly' => false,
                        'count' => 0,
                        'severity' => 'info',
                    ]),
                ])
                ->values();

            return [
                'row_key' => $row->row_key,
                'id' => $row->id,
                'code' => $row->code,
                'name' => $row->name,
                'contribution' => $row->contribution,
                'fk_programme' => $row->fk_programme,
                'programme_code' => $row->programme_code,
                'programme_name' => $row->programme_name,
                'level_contribution' => $aat->level_contribution,
                'ues' => $ues,
            ];
        });

        return response()->json($aavs);
    }

    public function store(Request $request)
    {
        $universityId = Auth::user()->university_id;

        $validated = $request->validate([
            'code'        => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('acquis_apprentissage_terminaux', 'code')
                    ->where(fn($q) => $q->where('university_id', $universityId)),
            ],
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',
            'level_contribution' => 'required|integer|min:3|max:10',
            'fk_programme' => [
                'required',
                'integer',
                Rule::exists('programme', 'id')
                    ->where(fn($query) => $query->where('university_id', $universityId)),
            ],
        ]);
        // ----- Génération du code AATxxx -----


        // Ajout du code au tableau validé
        // ✅ si vide => générer
        if (empty($validated['code'])) {
            $validated['code'] = $this->codeGen->nextAAT();
        }
        $validated['university_id'] = $universityId;
        $validated['fk_programme'] = (int) $validated['fk_programme'];


        $aav = AAT::create($validated);

        return response()->json([
            'success' => true,
            'id' => $aav->id,
            'aav' => $aav,
            'message' => "AAT créé avec succès."
        ]);
    }
}
