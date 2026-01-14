<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux as AAT;
use App\Models\AcquisApprentissageVise as AAV;
use App\Models\AcquisApprentissageVise;
use App\Models\ElementConstitutif;
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

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
        ]);
        $aat = AAT::findOrFail($validated['id']);
        $aat->delete();
        return response()->json([
            'success' => true,
            'message' => "Acquis d'apprentissage terminal supprimé avec succès.",
        ]);
    }

    public function getTree(Request $request)
    {
            $validated = $request->validate([
        'id' => 'required|integer|exists:acquis_apprentissage_terminaux,id',
    ]);

    $aatId = (int) $validated['id'];

    $aat = AAT::findOrFail($aatId);

    // 1) UEs "racines" liées à cet AAT (et pas des enfants)
    $ues = UniteEnseignement::query()
        ->select('unite_enseignement.id', 'unite_enseignement.code', 'unite_enseignement.name', 'unite_enseignement.ects')
         ->whereHas('aavvise.aats', function ($q) use ($aatId) {
            $q->where('acquis_apprentissage_terminaux.id', $aatId);
        }) 
        ->whereNotIn('unite_enseignement.id', function ($query) {
            $query->select('fk_ue_child')->from('element_constitutif');
        })
        ->orderBy('unite_enseignement.code')
        ->get();

    // 2) Pour chaque UE, on ajoute ses AAV (UE + EC)
    $ues->each(function ($ue) use ($aatId) {

        // ids EC directs
        $childIds = ElementConstitutif::where('fk_ue_parent', $ue->id)
            ->pluck('fk_ue_child')
            ->toArray();

        $ueIds = array_unique(array_merge([$ue->id], $childIds));

        // AAV venant de l'UE ou de ses EC, filtrés sur l'AAT demandé
        $aavs = AAV::query()
            ->select(
                'acquis_apprentissage_vise.id as id',
                'acquis_apprentissage_vise.code as code',
                'acquis_apprentissage_vise.name as name',
                'aav_aat.contribution as contribution',

                'ue_source.id as ue_source_id',
                'ue_source.code as ue_source_code',
                'ue_source.name as ue_source_name'
            )
            ->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->join('aav_aat', 'aav_aat.fk_aav', '=', 'acquis_apprentissage_vise.id')
            ->join('unite_enseignement as ue_source', 'ue_source.id', '=', 'aavue_vise.fk_unite_enseignement')
            ->where('aav_aat.fk_aat', $aatId)
            ->whereIn('aavue_vise.fk_unite_enseignement', $ueIds)
            ->orderBy('ue_source.code')
            ->orderBy('acquis_apprentissage_vise.code')
            ->get();

        // optionnel : marquer si ça vient d'un EC
        $aavs->each(function ($row) use ($ue) {
            $row->origine_type = ((int) $row->ue_source_id === (int) $ue->id) ? 'UE' : 'EC';
        });

        // on attache au modèle (pas besoin que ce soit une vraie relation)
        $ue->setAttribute('aavvise', $aavs);
    });

    $aat->setAttribute('ues', $ues);

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
