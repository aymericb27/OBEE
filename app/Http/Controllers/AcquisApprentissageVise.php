<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
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
