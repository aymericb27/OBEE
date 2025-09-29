<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use Illuminate\Http\Request;

class AcquisApprentissageTerminaux extends Controller
{
    public function get()
    {
        $result = AAT::select('id', 'code', 'name', 'description')
            ->get();
        return $result;
    }
    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAT::select('code', 'id', 'name', 'description')
            ->where('acquis_apprentissage_terminaux.id', $validated['id'])
            ->first();

        return $response;
    }

    public function getAAVs(Request $request){
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('code', 'id', 'name')
            ->where('fk_AAT', $validated['id'])
            ->get();
        return $response;
    }
}
