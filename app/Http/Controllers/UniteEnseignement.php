<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;
use App\Http\Controllers\ErrorController;
use App\Models\Programme;
use App\Models\UEPRO;

class UniteEnseignement extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ects' => 'required|integer|max:200',
            'description' => 'required|string|max:2024',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['array'],
            'pro.*.id' => ['nullable', 'integer', 'exists:programme,id'],
            'pro.*.semester' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        // ----- Génération du code UExxx -----
        // Récupère le dernier code existant
        $lastUE = UE::where('code', 'LIKE', 'UE%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastUE) {
            // extrait le numéro : PRO012 → 12
            $lastNumber = intval(substr($lastUE->code, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1; // premier code
        }

        // format UE001 / UE024 / UE300…
        $newCode = 'UE' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Ajout du code au tableau validé
        $validated['code'] = $newCode;
        $ue = UE::create([
            'name' => $validated['name'],
            'ects' => $validated['ects'],
            'code' => $validated['code'],
            'description' => $validated['description'],
        ]);

        // ✅ Mise à jour des relations (si tu as des tables pivots)
        if (isset($validated['aavvise'])) {
            $ue->aavvise()->sync(array_column($validated['aavvise'], 'id'));
        }

        if (isset($validated['aavprerequis'])) {
            $ue->aavprerequis()->sync(array_column($validated['aavprerequis'], 'id'));
        }
        if (!empty($validated['pro'])) {

            $pivotData = [];

            foreach ($validated['pro'] as $item) {
                $pivotData[$item['id']] = ['semester' => $item['semester']];
            }

            // ajoute tous les liens pivot d'un coup
            $ue->pro()->attach($pivotData);
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
        // Convertir onlyErrors en bool si présent
        if ($request->has('onlyErrors')) {
            $request->merge([
                'onlyErrors' => filter_var($request->onlyErrors, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validated = $request->validate([
            'onlyErrors' => 'nullable|boolean',
            'semestre'   => 'nullable|integer|in:1,2',
            'program'    => 'nullable|integer|exists:programme,id',
        ]);

        $ues = UE::select('unite_enseignement.id', 'code', 'name', 'ects')
            ->with(['prerequis', 'vise']);

        // ✔ programme filtré seulement si fourni
        if (!empty($validated['program'])) {
            $ues->join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('fk_programme', $validated['program']);
        }

        // ✔ semestre filtré seulement si fourni
        if (!empty($validated['semestre'])) {
            $ues->where('semestre', $validated['semestre']);
        }

        $ues = $ues->get();

        // Analyse d'erreurs
        $EC = new ErrorController;
        $result = $EC->getErrorUES($ues, true);

        // ✔ onlyErrors appliqué uniquement si provided et true
        if (!empty($validated['onlyErrors'])) {

            $result = collect($result)
                ->filter(fn($ue) => isset($ue->error) && $ue->error === true)
                ->values();
        }

        return $result;
    }
}
