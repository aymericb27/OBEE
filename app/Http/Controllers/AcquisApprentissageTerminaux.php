<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\AcquisApprentissageVise;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;

class AcquisApprentissageTerminaux extends Controller
{
    public function get()
    {
        $result = AAT::select('id', 'code', 'name', 'description')
            ->get();
        return $result;
    }
    public function getTree(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $aat = AAT::where('id', $validated['id'])->first();
        $ues = UniteEnseignement::select(
            'code',
            'name',
            'unite_enseignement.id',
            'ects',
            'ue_aat.contribution'
        )
            ->join('ue_aat', 'fk_ue', '=', 'unite_enseignement.id')
            ->where('fk_aat', $validated['id'])

            // exclure les children dans la liste principale
            ->whereNotIn('unite_enseignement.id', function ($query) {
                $query->select('fk_ue_child')->from('element_constitutif');
            })

            // charger les enfants et leur contribution
            ->with([
                'children' => function ($q) use ($validated) {
                    $q->select('unite_enseignement.id', 'code', 'name', 'ects', 'ue_aat.contribution', 'element_constitutif.contribution as ECContribution')
                        ->join('ue_aat', 'fk_ue', '=', 'unite_enseignement.id')
                        ->where('fk_aat', $validated['id']);
                }
            ])

            ->get();
        $aat->ues = $ues;
        $aavs = AcquisApprentissageVise::select('code', 'contribution', 'name', 'id')
            ->where('fk_AAT', $validated['id'])->get();
        $aat->aavs = $aavs;
        return $aat;
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

    public function getAAVs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('code', 'id', 'name')
            ->where('fk_AAT', $validated['id'])
            ->get();
        return $response;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',
        ]);
        // ----- Génération du code AATxxx -----
        // Récupère le dernier code existant
        $lastAAT = AAT::where('code', 'LIKE', 'AAT%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastAAT) {
            // extrait le numéro : PRO012 → 12
            $lastNumber = intval(substr($lastAAT->code, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1; // premier code
        }

        // format UE001 / UE024 / UE300…
        $newCode = 'AAT' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Ajout du code au tableau validé
        $validated['code'] = $newCode;

        $aav = AAT::create($validated);

        return response()->json([
            'success' => true,
            'aav' => $aav,
            'message' => "AAV créé avec succès."
        ]);
    }
}
