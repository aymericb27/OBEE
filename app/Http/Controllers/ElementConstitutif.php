<?php

namespace App\Http\Controllers;

use App\Models\ElementConstitutif as EC;
use App\Models\UEEC;
use Illuminate\Http\Request;

class ElementConstitutif extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'selectedUE' => 'required|array|max:2000',
            'description' => 'required|string|max:2024',
        ]);
        $ec = EC::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,

        ]);
        foreach ($request->selectedUE as $ueid) {
            UEEC::create([
                'fk_unite_enseignement' => $ueid,
                'fk_element_constitutif' => $ec->id,
            ]);
        }

        return response()->json(['message' => 'Element constitutif enregistré avec succès']);
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'ecid' => 'required|integer',
        ]);
        $response = EC::select('code as ECCode', 'id as ECid', 'name as ECname', 'description as ECDescription')
            ->where('element_constitutif.id', $validated['ecid'])
            ->first();

/*         $response->aavs = AcquisApprentissageVise::select('acquis_apprentissage_terminaux.code as AATCode', 'acquis_apprentissage_vise.description as AAVDescription', 'acquis_apprentissage_vise.code as AAVCode')
            ->leftJoin('aavue', 'aavue.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->leftJoin('acquis_apprentissage_terminaux', 'acquis_apprentissage_terminaux.id', '=', 'acquis_apprentissage_vise.fk_AAT')
            ->where('aavue.fk_unite_enseignement', $validated['ueid'])
            ->get();

        $response->ecs = ElementConstitutif::select('code as ECCode', 'element_constitutif.id as ECId', 'name as ECName')
            ->join('ueec', 'fk_element_constitutif', '=', 'element_constitutif.id')
            ->where('fk_unite_enseignement', $validated['ueid'])
            ->get(); */
        return $response;
    }

    public function get(Request $request)
    {
        return EC::get();
    }
}
