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
            'unite_enseignement.id',
            'unite_enseignement.code',
            'unite_enseignement.name',
            'unite_enseignement.ects'
        )
            ->whereHas('aavvise.aats', function ($q) use ($validated) {
                $q->where('acquis_apprentissage_terminaux.id', $validated['id']);
            })
            ->whereNotIn('unite_enseignement.id', function ($query) {
                $query->select('fk_ue_child')
                    ->from('element_constitutif');
            })
            ->with([
                'aavvise' => function ($q) use ($validated) {
                    $q->join('aav_aat', 'aav_aat.fk_aav', '=', 'acquis_apprentissage_vise.id')
                        ->where('aav_aat.fk_aat', $validated['id'])
                        ->select(
                            'acquis_apprentissage_vise.id',
                            'acquis_apprentissage_vise.code',
                            'acquis_apprentissage_vise.name',
                            'aav_aat.contribution'
                        );
                }
            ])
            ->get();

        $aat->ues = $ues;

        return $aat;
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:acquis_apprentissage_terminaux,id'],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:2024',
        ]);

        /*
     |--------------------------------------------------------------------------
     | Récupération AAT
     |--------------------------------------------------------------------------
     */
        $aat = AAT::findOrFail($validated['id']);

        /*
     |--------------------------------------------------------------------------
     | Mise à jour AAT
     |--------------------------------------------------------------------------
     */
        $aat->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'AAT mis à jour avec succès.',
        ]);
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
            'id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
        ]);

        $aavs = AAV::whereHas('aats', function ($q) use ($validated) {
            $q->where('acquis_apprentissage_terminaux.id', $validated['id']);
        })
            ->select('id', 'code', 'name')
            ->get()
            ->map(function ($aav) {
                return [
                    'id' => $aav->id,
                    'code' => $aav->code,
                    'name' => $aav->name,
                    'contribution' => $aav->aats->first()->pivot->contribution ?? null
                ];
            });
        return $aavs;
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
