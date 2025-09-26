<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as ModelsAcquisApprentissageTerminaux;
use Illuminate\Http\Request;

class AcquisApprentissageTerminaux extends Controller
{
    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'aatid' => 'required|integer',
        ]);
        $response = ModelsAcquisApprentissageTerminaux::select('code as AATCode', 'id as AATId', 'name as AATName', 'description as AATDescription')
            ->where('acquis_apprentissage_terminaux.id', $validated['aatid'])
            ->first();

        return $response;
    }
}
