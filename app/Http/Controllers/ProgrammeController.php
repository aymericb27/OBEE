<?php

namespace App\Http\Controllers;

use App\Exports\ProgExport;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Expr\Cast\Array_;
use PhpParser\Node\Expr\Cast\Object_;

class ProgrammeController extends Controller
{
    public function get(Request $request = null)
    {
        $programmes = Programme::select('code', 'id', 'ects', 'name')->get();
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
            'message' => 'Programme created successfully',
            'programme' => $programme
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
                ->where('semestre', $i)->sum('ects');
            $listSemestre[$i]->number = $i;
        }
        $programme->listSemestre = $listSemestre;

        return response()->json($programme);
    }

    public function getUEBySemester($id, $semestre)
    {
        $ue = UniteEnseignement::join('ue_programme', 'fk_unite_enseignement', '=', 'unite_enseignement.id')
            ->where('fk_programme', $id)
            ->where('semestre', $semestre)->get();
        return $ue;
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = Programme::select('code', 'id', 'name', 'ects')
            ->where('id', $validated['id'])
            ->first();
        return $response;
    }
}
