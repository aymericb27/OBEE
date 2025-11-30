<?php

namespace App\Http\Controllers;

use App\Models\Programme;
use App\Models\UEPRO;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;

class ProgrammeController extends Controller
{
    public function get(Request $request = null)
    {
        $programmes = Programme::select('code', 'id', 'ects', 'name', 'semestre as nbrSemester')->get();
        return $programmes;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'ects'     => 'required|integer|min:0',
            'semestre' => 'required|integer|min:1',
        ]);

        // ----- Génération du code PROxxx -----
        // Récupère le dernier code existant
        $lastProgramme = Programme::where('code', 'LIKE', 'PRO%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastProgramme) {
            // extrait le numéro : PRO012 → 12
            $lastNumber = intval(substr($lastProgramme->code, 3));
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1; // premier code
        }

        // format PRO001 / PRO024 / PRO300…
        $newCode = 'PRO' . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

        // Ajout du code au tableau validé
        $validated['code'] = $newCode;

        // ----- Création du programme -----
        $programme = Programme::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Le programme a été crée correctement',
            'id' => $programme->id
        ]);
    }
    public function addSemestre(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:programme,id',
        ]);

        // Récupération du programme
        $programme = Programme::findOrFail($validated['id']);

        // Incrémentation du nombre de semestres
        $programme->semestre = $programme->semestre + 1;
        $programme->save();

        // Retourne le programme mis à jour avec la nouvelle structure de semestres

        return response()->json([
            'success' => true,
            'message' => 'Le semestre a été crée correctement',
            'id' => $programme->id
        ]);
    }
    /**
     * Update an existing programme
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'id'       => 'required|integer|exists:programme,id',
            'name'     => 'required|string|max:255',
            'ects'     => 'required|integer|min:0',
            'semestre' => 'required|integer|min:1',
        ]);

        $programme = Programme::findOrFail($validated['id']);

        // Update fields
        $programme->update([
            'name'     => $validated['name'],
            'ects'     => $validated['ects'],
            'semestre' => $validated['semestre'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Programme updated successfully',
            'programme' => $programme
        ]);
    }

    public function addUEs(Request $request)
    {
        $validated = $request->validate([
            'list' => 'required|array',
            'id' => 'required|integer',
            'semester' => 'required|integer',
        ]);
        foreach ($validated['list'] as $ue) {
            UEPRO::create([
                "fk_unite_enseignement" => $ue['id'],
                "fk_programme" => $validated['id'],
                "semester" => $validated['semester'],
            ]);
        }
        return response()->json([
            'success' => true,
            'message' => "Unité(s) d'enseignement(s) rajoutée(s) avec succès",
        ]);
    }

    public function getUE(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ues = UniteEnseignement::join('ue_programme', 'fk_unite_enseignement','=','unite_enseignement.id')->where('fk_programme', $validated['id'])->get();
        return $ues;
    }


    public function getTree(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $programme = Programme::select('code', 'id', 'name', 'ects', 'semestre as numberSemestre')
            ->where('id', $validated['id'])
            ->firstOrFail();
        $programme->listSemestre = [];

        for ($i = 1; $i <= $programme->numberSemestre; $i++) {
            $listSemestre[$i] = new \stdClass();
            $listSemestre[$i]->UES = $this->getUEBySemester($validated['id'], $i);
            $listSemestre[$i]->countECTS =  UniteEnseignement::join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('fk_programme', $validated['id'])
                ->where('semester', $i)->sum('ects');
            $listSemestre[$i]->number = $i;
        }
        $programme->listSemestre = $listSemestre;

        return response()->json($programme);
    }

    public function getUEBySemester($id, $semestre)
    {
        $ues = UniteEnseignement::select('code', 'name', 'unite_enseignement.id', 'ects')
            ->join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('fk_programme', $id)
            ->where('semester', $semestre)
            ->whereNotIn('unite_enseignement.id', function ($query) {
                $query->select('fk_ue_child')->from('element_constitutif');
            })
            ->with('children')   // <-- chargement des enfants via pivot
            ->get();
        return $ues;
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('code', 'id', 'name', 'ects', 'semestre as nbrSemester')
            ->where('id', $validated['id'])
            ->first();
        return $response;
    }
}
