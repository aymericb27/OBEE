<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;
use App\Http\Controllers\ErrorController;
use App\Models\AcquisApprentissageTerminaux;
use App\Models\ElementConstitutif;
use App\Models\Programme;
use App\Models\UEPRO;

class UniteEnseignement extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ects' => 'nullable|integer|max:200',
            'description' => 'nullable|string|max:2024',
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['array'],
            'pro.*.id' => ['nullable', 'integer', 'exists:programme,id'],
            'pro.*.semester' => ['nullable', 'integer', 'min:1'],
            'aat' => ['array'],
            'aat.*.id' => ['nullable', 'integer', 'exists:acquis_apprentissage_terminaux,id'],
            'aat.*.contribution' => ['nullable', 'integer', 'min:1', 'max:3'],
            'ueParentID' => ["nullable", 'integer', 'exists:unite_enseignement,id'],
            'ueParentContribution' => ["nullable", 'integer', 'min:1', 'max:3']
        ]);

        // ----- Génération du code UExxx -----
        // Récupère le dernier code existant
        $lastUE = UE::where('code', 'LIKE', 'UE%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastUE) {
            // extrait le numéro : PRO012 → 12
            $lastNumber = intval(substr($lastUE->code, 2));
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
            $ue->prerequis()->sync(array_column($validated['aavprerequis'], 'id'));
        }

        if (!empty($validated['aat'])) {

            $pivotData = [];
            foreach ($validated['aat'] as $item) {
                $pivotData[$item['id']] = ['contribution' => $item['contribution']];
            }

            // ajoute tous les liens pivot d'un coup
            $ue->aat()->attach($pivotData);
        }
        if (!empty($validated['pro'])) {

            $pivotData = [];

            foreach ($validated['pro'] as $item) {
                $pivotData[$item['id']] = ['semester' => $item['semester']];
            }

            // ajoute tous les liens pivot d'un coup
            $ue->pro()->attach($pivotData);
        }
        if (!empty($validated['ueParentID'])) {
            ElementConstitutif::create([
                'fk_ue_parent' => $validated['ueParentID'],
                'fk_ue_child' => $ue->id,
                'contribution' => $validated['ueParentContribution']
            ]);
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
            'ects' => ['nullable', 'integer'],
            'aavprerequis' => ['array'],
            'aavprerequis.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'aavvise' => ['array'],
            'aavvise.*.id' => ['integer', 'exists:acquis_apprentissage_vise,id'],
            'pro' => ['array'],
            'pro.*.id' => ['integer', 'exists:programme,id'],
            'pro.*.semester' => ['nullable', 'integer', 'min:1'],
            'aat' => ['array'],
            'aat.*.id' => ['nullable', 'integer', 'exists:acquis_apprentissage_terminaux,id'],
            'aat.*.contribution' => ['nullable', 'integer', 'min:1', 'max:3'],
            'ueParentID' => ["nullable", 'integer', 'exists:unite_enseignement,id'],
            'ueParentContribution' => ["nullable", 'integer', 'min:1', 'max:3']
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
            $ue->prerequis()->sync(array_column($validated['aavprerequis'], 'id'));
        }
        if (isset($validated['pro'])) {

            $pivotData = [];
            foreach ($validated['pro'] as $item) {
                $pivotData[$item['id']] = ['semester' => $item['semester']];
            }
            $ue->pro()->sync($pivotData);
        }
        if (isset($validated['aat'])) {

            $pivotData = [];
            foreach ($validated['aat'] as $item) {
                $pivotData[$item['id']] = ['contribution' => $item['contribution']];
            }

            // ajoute tous les liens pivot d'un coup
            $ue->aat()->sync($pivotData);
        }
        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement mise à jour avec succès.",
        ]);
    }

    public function getPro(Request $request)
    {

        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('programme.id', 'name', 'code', 'semester')
            ->join('ue_programme', 'fk_programme', '=', 'programme.id')
            ->where('fk_unite_enseignement', $validated['id'])->get();

        return $response;
    }


    public function getAATs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AcquisApprentissageTerminaux::select('acquis_apprentissage_terminaux.id', 'name', 'code', 'contribution')
            ->join('ue_aat', 'fk_aat', '=', 'acquis_apprentissage_terminaux.id')
            ->where('fk_ue', $validated['id'])->get();
        return $response;
    }
    public function addEC(Request $request)
    {
        $validated = $request->validate([
            'idParent' => 'required|integer',
            'listChild' => 'required|array',
        ]);

        foreach ($validated['listChild'] as $index => $EC) {
            ElementConstitutif::create([
                'fk_ue_parent' => $validated['idParent'],
                'fk_ue_child' => $EC['id'],
                'contribution' => 1
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement mise à jour avec succès.",
        ]);
    }

    public function getChildren(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $ues =UE::select('unite_enseignement.id', 'unite_enseignement.code', 'unite_enseignement.name', 'element_constitutif.contribution')
        ->join('element_constitutif', 'element_constitutif.fk_ue_child', '=', 'unite_enseignement.id')
        ->where('element_constitutif.fk_ue_parent', $validated['id'])
        ->orderBy('unite_enseignement.code')
        ->get();

        return $ues;
    }

    public function getAAVvise(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);

        $ueId = (int) $validated['id'];

        // 1) IDs des UEs à prendre en compte : UE + ses éléments constitutifs
        // ⚠️ Adapte le nom des colonnes de la table element_constitutif si besoin
        // récupère les EC (UE enfants)
        $ecIds = ElementConstitutif::where('fk_ue_parent', $ueId)
            ->pluck('fk_ue_child')
            ->toArray();
        $ueIds = array_unique(array_merge([$ueId], $ecIds));

        // 2) AAV vise de toutes ces UEs
        $response = AAV::query()
            ->select(
                'acquis_apprentissage_vise.id as id',
                'acquis_apprentissage_vise.code as code',
                'acquis_apprentissage_vise.name as name',

                'ue.id as ue_source_id',
                'ue.code as ue_source_code',
                'ue.name as ue_source_name'
            )
            ->join('aavue_vise', 'aavue_vise.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->join('unite_enseignement as ue', 'ue.id', '=', 'aavue_vise.fk_unite_enseignement')
            ->whereIn('aavue_vise.fk_unite_enseignement', $ueIds)
            ->orderBy('ue.code')
            ->orderBy('acquis_apprentissage_vise.code')
            ->get();

        return $response;


        return $response;
    }

    public function getAAVviseOnlyParent(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('acquis_apprentissage_vise.id', 'name', 'code')
            ->join('aavue_vise', 'fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->where('aavue_vise.fk_unite_enseignement', $validated['id'])
            ->groupBy('acquis_apprentissage_vise.id', 'name', 'code')
            ->havingRaw('COUNT(aavue_vise.fk_unite_enseignement) = 1')
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
            ->with(['parent', 'children'])
            ->first();
        return $response;
    }

    public function delete(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ue = UE::findOrFail($validated['id']);
        $ue->delete();
        return response()->json([
            'success' => true,
            'message' => "Unité d'enseignement supprimé avec succès.",
        ]);
    }

    public function get(Request $request)
    {
        /*         // Convertir onlyErrors en bool si présent
        if ($request->has('onlyErrors')) {
            $request->merge([
                'onlyErrors' => filter_var($request->onlyErrors, FILTER_VALIDATE_BOOLEAN),
            ]);
        }

        $validated = $request->validate([
            'program'    => 'nullable|integer|exists:programme,id',
        ]); */

        $ues = UE::select('unite_enseignement.id', 'code', 'name', 'ects');

        /*         // ✔ programme filtré seulement si fourni
        if (!empty($validated['program'])) {
            $ues->join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('fk_programme', $validated['program']);
        }

        // ✔ semestre filtré seulement si fourni
        if (!empty($validated['semestre'])) {
            $ues->where('semestre', $validated['semestre']);
        } */

        $ues = $ues->get();

        // Analyse d'erreurs
        /*  $EC = new ErrorController;
        $result = $EC->getErrorUES($ues, true);

        // ✔ onlyErrors appliqué uniquement si provided et true
        if (!empty($validated['onlyErrors'])) {

            $result = collect($result)
                ->filter(fn($ue) => isset($ue->error) && $ue->error === true)
                ->values();
        }
 */
        return $ues;
    }
}
