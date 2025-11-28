<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;

class AcquisApprentissageVise extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',
            'fk_AAT'      => 'required|integer|exists:acquis_apprentissage_terminaux,id',
            'contribution' => ['nullable', 'integer', 'min:1', 'max:3'],

        ]);
        // ----- Génération du code UExxx -----
        // Récupère le dernier code existant
        $lastAAV = AAV::where('code', 'LIKE', 'AAV%')
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
        $newCode = 'AAV' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Ajout du code au tableau validé
        $validated['code'] = $newCode;

        $aav = AAV::create($validated);

        return response()->json([
            'success' => true,
            'aav' => $aav,
            'message' => "AAV créé avec succès."
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
            'id' => 'required|integer',
        ]);
        $response = AAV::select('acquis_apprentissage_terminaux.code', 'acquis_apprentissage_terminaux.id', 'acquis_apprentissage_terminaux.name')
            ->join('acquis_apprentissage_terminaux', 'fk_AAT', '=', 'acquis_apprentissage_terminaux.id')
            ->where('acquis_apprentissage_vise.id', $validated['id'])
            ->get();
        return $response;
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
