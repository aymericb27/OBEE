<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\AcquisApprentissageVise as ModelsAcquisApprentissageVise;
use App\Models\Programme;
use App\Models\UniteEnseignement as UE;
use App\Services\CodeGeneratorService;
use App\Services\UEAnomalyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AcquisApprentissageVise extends Controller
{
    public function __construct(
        private CodeGeneratorService $codeGen,
        private UEAnomalyService $ueAnomalyService
    ) {}

    public function store(Request $request)
    {
        $universityId = Auth::user()->university_id;

        $validated = $request->validate([
            'name' => 'required|string|max:1000',
            'description' => 'nullable|string|max:2024',

            'aat' => 'nullable|array',
            'aat.*.id' => [
                'nullable',
                'integer',
                'distinct',
                Rule::exists('acquis_apprentissage_terminaux', 'id')
                    ->where(fn($query) => $query->where('university_id', $universityId)),
            ],
            'aat.*.contribution' => 'nullable|integer|min:1|max:10',
        ]);

        $aatRows = collect($validated['aat'] ?? [])
            ->map(fn($row) => [
                'id' => (int) $row['id'],
                'contribution' => isset($row['contribution']) ? (int) $row['contribution'] : null,
            ])
            ->values()
            ->all();

        /*
     |--------------------------------------------------------------------------
     | Generation du code AAVxxx
     |--------------------------------------------------------------------------
     */
        $validated['code'] = $this->codeGen->nextAAV();
        /*
     |--------------------------------------------------------------------------
     | Creation de l'AAV
     |--------------------------------------------------------------------------
     */
        $aav = AAV::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'],
            'university_id' => $universityId,
        ]);

        /*
     |--------------------------------------------------------------------------
     | Preparation des donnees pivot
     |--------------------------------------------------------------------------
     */
        $pivotRows = [];

        foreach ($aatRows as $aat) {
            $pivotRows[] = [
                'fk_aav' => $aav->id,
                'fk_aat' => $aat['id'],
                'contribution' => $aat['contribution'],
                'university_id' => $universityId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if (!empty($pivotRows)) {
            DB::table('aav_aat')->insert($pivotRows);
        }

        $this->ueAnomalyService->recomputeForAAV((int) $aav->id, $universityId);

        return response()->json([
            'success' => true,
            'aav' => $aav->load('aats'),
            'message' => "AAV cree avec succes."
        ], 201);
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_vise,id',
        ]);
        $aavId = (int) $validated['id'];
        $universityId = (int) Auth::user()->university_id;
        $impactedUeIds = DB::table('aavue_vise')
            ->where('university_id', $universityId)
            ->where('fk_acquis_apprentissage_vise', $aavId)
            ->pluck('fk_unite_enseignement')
            ->merge(
                DB::table('aavue_prerequis')
                    ->where('university_id', $universityId)
                    ->where('fk_acquis_apprentissage_prerequis', $aavId)
                    ->pluck('fk_unite_enseignement')
            )
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $aav = AAV::findOrFail($validated['id']);
        $aav->delete();

        $this->ueAnomalyService->recomputeForUEIds($impactedUeIds);

        return response()->json([
            'success' => true,
            'message' => "Acquis d'apprentissage vise supprime avec succes.",
        ]);
    }

    public function getAAVPROPrerequis(Request $request)
    {
        $aav = AAV::select('acquis_apprentissage_vise.id', 'code', 'name')->join('aavpro_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')->distinct()
            ->get();

        return $aav;
    }

    public function getPROPrerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('code', 'programme.id', 'name')
            ->join('aavpro_prerequis', 'fk_programme', '=', 'programme.id')
            ->where('fk_acquis_apprentissage_prerequis', $validated['id'])
            ->get();

        return $response;
    }

    public function update(Request $request)
    {
        $universityId = Auth::user()->university_id;

        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:acquis_apprentissage_vise,id'],

            'name' => 'required|string|max:1000',
            'description' => 'nullable|string|max:2024',

            'aats' => 'nullable|array',
            'aats.*.id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('acquis_apprentissage_terminaux', 'id')
                    ->where(fn($query) => $query->where('university_id', $universityId)),
            ],
            'aats.*.contribution' => 'required|integer|min:1|max:10',
        ]);

        $aatRows = collect($validated['aats'] ?? [])
            ->map(fn($row) => [
                'id' => (int) $row['id'],
                'contribution' => isset($row['contribution']) ? (int) $row['contribution'] : null,
            ])
            ->values()
            ->all();

        /*
     |--------------------------------------------------------------------------
     | Recuperation AAV
     |--------------------------------------------------------------------------
     */
        $aav = AAV::findOrFail($validated['id']);

        DB::transaction(function () use ($aav, $validated, $aatRows, $universityId) {
            /*
         |--------------------------------------------------------------------------
         | Mise a jour AAV
         |--------------------------------------------------------------------------
         */
            $aav->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            DB::table('aav_aat')
                ->where('fk_aav', $aav->id)
                ->where('university_id', $universityId)
                ->delete();

            $pivotRows = [];
            foreach ($aatRows as $aat) {
                $pivotRows[] = [
                    'fk_aav' => $aav->id,
                    'fk_aat' => $aat['id'],
                    'contribution' => $aat['contribution'],
                    'university_id' => $universityId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($pivotRows)) {
                DB::table('aav_aat')->insert($pivotRows);
            }
        });

        $this->ueAnomalyService->recomputeForAAV((int) $aav->id, $universityId);

        return response()->json([
            'success' => true,
            'message' => 'AAV mis a jour avec succes.',
            'aav' => $aav->load('aats')
        ]);
    }


    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('code', 'id', 'name', 'description')
            ->where('acquis_apprentissage_vise.id', $validated['id'])
            ->first();

        return $response;
    }

    public function getUEvise(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = UE::select('code', 'unite_enseignement.id', 'name')
            ->join('aavue_vise', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('fk_acquis_apprentissage_vise', $validated['id'])
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

        return $response;
    }

    public function getUEprerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = UE::select('code', 'unite_enseignement.id', 'name')
            ->join('aavue_prerequis', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('fk_acquis_apprentissage_prerequis', $validated['id'])
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

        return $response;
    }

    public function getPrerequis()
    {
        $response = AAV::prerequis();
        return $response;
    }

    public function getAATs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_vise,id',
        ]);

        $response = DB::table('aav_aat')
            ->join(
                'acquis_apprentissage_terminaux',
                'acquis_apprentissage_terminaux.id',
                '=',
                'aav_aat.fk_aat'
            )
            ->leftJoin('programme', 'programme.id', '=', 'acquis_apprentissage_terminaux.fk_programme')
            ->where('aav_aat.fk_aav', $validated['id'])
            ->select(
                'aav_aat.id as row_key',
                'acquis_apprentissage_terminaux.id',
                'acquis_apprentissage_terminaux.code',
                'acquis_apprentissage_terminaux.name',
                'acquis_apprentissage_terminaux.level_contribution',
                'aav_aat.contribution',
                'acquis_apprentissage_terminaux.fk_programme',
                'programme.code as programme_code',
                'programme.name as programme_name',
            )
            ->orderBy('acquis_apprentissage_terminaux.code')
            ->orderBy('programme.code')
            ->get();

        return response()->json($response);
    }

    public function get(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|integer|exists:programme,id',
        ]);

        $result = AAV::query()
            ->select('acquis_apprentissage_vise.id', 'acquis_apprentissage_vise.code', 'acquis_apprentissage_vise.name');

        if (!empty($validated['program_id'])) {
            $result->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
                ->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'aavue_vise.fk_unite_enseignement')
                ->where('ue_programme.fk_programme', (int) $validated['program_id'])
                ->distinct();
        }

        $result = $result->get();
        return $result;
    }

    public function getOnlyPrerequis(Request $request)
    {
        $validated = $request->validate([
            'program_id' => 'nullable|integer|exists:programme,id',
        ]);

        $response = AAV::select(
            'acquis_apprentissage_vise.id',
            'acquis_apprentissage_vise.code',
            'acquis_apprentissage_vise.name'
        )
            ->join(
                'aavue_prerequis',
                'aavue_prerequis.fk_acquis_apprentissage_prerequis',
                '=',
                'acquis_apprentissage_vise.id'
            )
            ->distinct();

        if (!empty($validated['program_id'])) {
            $response->join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'aavue_prerequis.fk_unite_enseignement')
                ->where('ue_programme.fk_programme', (int) $validated['program_id']);
        }

        $response = $response->get();

        return $response;
    }
}
