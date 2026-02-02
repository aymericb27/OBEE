<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use App\Services\CodeGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AcquisApprentissageVise extends Controller
{
    public function __construct(private CodeGeneratorService $codeGen) {}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',

            'aat' => 'nullable|array',
            'aat.*.id' => 'nullable|integer|exists:acquis_apprentissage_terminaux,id',
            'aat.*.contribution' => 'nullable|integer|min:1|max:10',
        ]);

        /*
     |--------------------------------------------------------------------------
     | Génération du code AAVxxx
     |--------------------------------------------------------------------------
     */
        $validated['code'] = $this->codeGen->nextAAV();
        /*
     |--------------------------------------------------------------------------
     | Création de l’AAV
     |--------------------------------------------------------------------------
     */
        $aav = AAV::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'code' => $validated['code'],
            "university_id" => Auth::user()->university_id,
        ]);

        /*
     |--------------------------------------------------------------------------
     | Préparation des données pivot
     |--------------------------------------------------------------------------
     */
        $pivotData = [];

        foreach ($validated['aat'] as $aat) {
            $pivotData[$aat['id']] = [
                'contribution' => $aat['contribution'],
                "university_id" => Auth::user()->university_id,

            ];
        }

        /*
     |--------------------------------------------------------------------------
     | Sync AAV ↔ AAT
     |--------------------------------------------------------------------------
     */
        $aav->aats()->sync($pivotData);

        return response()->json([
            'success' => true,
            'aav' => $aav->load('aats'),
            'message' => "AAV créé avec succès."
        ], 201);
    }
    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_vise,id',
        ]);
        $aav = AAV::findOrFail($validated['id']);
        $aav->delete();
        return response()->json([
            'success' => true,
            'message' => "Acquis d'apprentissage visé supprimé avec succès.",
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:acquis_apprentissage_vise,id'],

            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',

            'aats' => 'nullable|array',
            'aats.*.id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
            'aats.*.contribution' => 'required|integer|min:1|max:10',
        ]);

        /*
     |--------------------------------------------------------------------------
     | Récupération AAV
     |--------------------------------------------------------------------------
     */
        $aav = AAV::findOrFail($validated['id']);

        /*
     |--------------------------------------------------------------------------
     | Mise à jour AAV
     |--------------------------------------------------------------------------
     */
        $aav->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        /*
     |--------------------------------------------------------------------------
     | Préparation pivot AAV ↔ AAT
     |--------------------------------------------------------------------------
     */
        $pivotData = [];

        foreach ($validated['aats'] as $aat) {
            $pivotData[$aat['id']] = [
                'contribution' => $aat['contribution'],
                'university_id' => Auth::user()->university_id,

            ];
        }

        /*
     |--------------------------------------------------------------------------
     | Sync pivot (insert / update / delete)
     |--------------------------------------------------------------------------
     */
        $aav->aats()->sync($pivotData);

        return response()->json([
            'success' => true,
            'message' => 'AAV mis à jour avec succès.',
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

        return $response;
    }

    public function getPrerequis()
    {
        $response = AAV::join('aavue_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')
            ->get();
        return $response;
    }

    public function getAATs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_vise,id',
        ]);

        $aav = AAV::with(['aats' => function ($query) {
            $query->select(
                'acquis_apprentissage_terminaux.id',
                'acquis_apprentissage_terminaux.code',
                'acquis_apprentissage_terminaux.name',
                'acquis_apprentissage_terminaux.level_contribution',
            );
        }])->findOrFail($validated['id']);

        $response = $aav->aats->map(function ($aat) {
            return [
                'id' => $aat->id,
                'code' => $aat->code,
                'name' => $aat->name,
                'contribution' => $aat->pivot->contribution,
                'level_contribution' => $aat->level_contribution,
            ];
        });

        return response()->json($response);
    }

    public function get()
    {
        $result = AAV::select('id', 'code', 'name')
            ->get();
        return $result;
    }

    public function getOnlyPrerequis()
    {
        $response = AAV::select(
            'acquis_apprentissage_vise.id',
            'acquis_apprentissage_vise.code',
            'acquis_apprentissage_vise.name'
        )
            ->join(
                'aavue_vise',
                'aavue_vise.fk_acquis_apprentissage_vise',
                '=',
                'acquis_apprentissage_vise.id'
            )
            ->distinct()
            ->get();

        return $response;
    }
}
