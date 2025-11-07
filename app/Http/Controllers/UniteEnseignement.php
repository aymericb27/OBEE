<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;
use App\Http\Controllers\ErrorController;
use App\Models\Programme;

class UniteEnseignement extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|max:200',
            'code' => 'required|string|max:10',
            'description' => 'required|string|max:2024',
            'semestre' => 'required|integer|max:2',
            'date_begin' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after:date_begin'],
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['array'],
            'pro.*.id' => ['integer', 'exists:programme,id'],
        ]);
        $ue = UE::create([
            'name' => $validated['name'],
            'ects' => $validated['ects'],
            'code' => $validated['code'],
            'semestre' => $validated['semestre'],
            'description' => $validated['description'],
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
        if (isset($validated['pro'])) {
            $ue->pro()->sync(array_column($validated['pro'], 'id'));
        }
        return response()->json([
            'success' => true,
            'id' => $ue->id,
            'message' => "Unité d'enseignement créé avec succès.",
        ]);
    }

    public function update(Request $request)
    {
        // ✅ Validation
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:unite_enseignement,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'semestre' => 'required|integer|max:2',
            'ects' => ['required', 'integer'],
            'date_begin' => ['required', 'date'],
            'date_end' => ['required', 'date', 'after:date_begin'],
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['array'],
            'pro.*.id' => ['integer', 'exists:programme,id'],
        ]);

        // ✅ Récupération de l’UE
        $ue = UE::findOrFail($validated['id']);

        // ✅ Mise à jour des champs de base
        $ue->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? '',
            'ects' => $validated['ects'],
            'semestre' => $validated['semestre'],
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
        if (isset($validated['pro'])) {
            $ue->pro()->sync(array_column($validated['pro'], 'id'));
        }
        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement mise à jour avec succès.",
            'ue' => $ue->load('aavvise', 'aavprerequis'),
        ]);
    }

    public function getPro(Request $request)
    {

        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('programme.id', 'name', 'code')
            ->join('ue_programme', 'fk_programme', '=', 'programme.id')
            ->where('fk_unite_enseignement', $validated['id'])->get();

        return $response;
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
        $response = UE::where('unite_enseignement.id', $validated['id'])
            ->first();
        return $response;
    }

    public function get(Request $request)
    {
        $request->merge([
            'onlyErrors' => filter_var($request->onlyErrors, FILTER_VALIDATE_BOOLEAN),
        ]);

        $validated = $request->validate([
            'onlyErrors' => 'nullable|boolean',
            'semestre' => 'nullable|integer|in:1,2',
            'program' => "sometimes|nullable|exists:programme,id"
        ]);
        $ues = UE::select('unite_enseignement.id', 'code', 'name', 'ects', 'date_begin', 'date_end', 'semestre')
            ->with(['prerequis', 'vise']);
        if ($validated['program']) {
            $ues->join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('fk_programme', $validated['program']);
        }
        if ($validated['semestre']) {
            $ues->where('semestre', $validated['semestre']);
        }
        $ues = $ues->get();

        $EC = new ErrorController;
        $result = $EC->getErrorUES($ues, true);

        // ✅ Si l’utilisateur veut seulement les UE avec erreurs
        if (!empty($validated['onlyErrors'])) {
            $ues = collect($result)->filter(function ($ue) {
                return isset($ue->error) && $ue->error === true;
            })->values();
            $result = $ues;
        }
        return $result;
    }
}
