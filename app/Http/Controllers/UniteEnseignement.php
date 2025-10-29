<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;

class UniteEnseignement extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|max:200',
            'code' => 'required|string|max:10',
            'description' => 'required|string|max:2024',
        ]);
        UE::create([
            'name' => $request->name,
            'ects' => $request->ects,
            'code' => $request->code,
            'description' => $request->description,
        ]);
        return UE::get();
    }

    public function update(Request $request)
    {
        // ✅ Validation
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:unite_enseignement,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'ects' => ['required', 'integer'],
            'date_begin' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after:date_begin'],
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
        ]);

        // ✅ Récupération de l’UE
        $ue = UE::findOrFail($validated['id']);

        // ✅ Mise à jour des champs de base
        $ue->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'ects' => $validated['ects'],
            'date_begin' => $validated['date_begin'],
            'date_end' => $validated['date_end'],
        ]);

        // ✅ Mise à jour des relations (si tu as des tables pivots)
        if (isset($validated['aavvise'])) {
            $ue->aavvise()->sync(array_column($validated['aavvise'], 'id'));
        }

        if (isset($validated['aavprerequis'])) {
            $ue->aavprerequis()->sync(array_column($validated['aavprerequis'], 'id'));
        }

        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement mise à jour avec succès.",
            'ue' => $ue->load('aavvise', 'aavprerequis'),
        ]);
    }

    public function getAAVvise(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('acquis_apprentissage_vise.id', 'name', 'code')
            ->join('aavue_vise', 'fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->where('aavue_vise.fk_unite_enseignement', $validated['id'])
            ->get();

        return $response;
    }

    public function getAAVprerequis(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('acquis_apprentissage_vise.id', 'name', 'code')
            ->join('aavue_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')
            ->where('aavue_prerequis.fk_unite_enseignement', $validated['id'])
            ->get();

        return $response;
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = UE::select('code', 'id', 'name', 'description', 'ects', 'date_begin', "date_end")
            ->where('unite_enseignement.id', $validated['id'])
            ->first();
        return $response;
    }

    public function get()
    {
        $ues = UE::select('id', 'name', 'ects', 'code', 'description', 'date_begin', 'date_end')->get();
        return $ues;
    }
}
