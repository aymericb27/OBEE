<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
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
            'aatid' => 'required|integer',
        ]);
        $response = AAT::select('code', 'id', 'name', 'description')
            ->where('acquis_apprentissage_terminaux.id', $validated['aatid'])
            ->first();

        return $response;
    }
}
