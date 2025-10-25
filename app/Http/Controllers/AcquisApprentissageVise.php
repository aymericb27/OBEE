<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;

class AcquisApprentissageVise extends Controller
{
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
}
