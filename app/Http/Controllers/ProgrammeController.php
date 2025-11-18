<?php

namespace App\Http\Controllers;

use App\Exports\ProgExport;
use App\Models\Programme;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpParser\Node\Expr\Cast\Object_;

class ProgrammeController extends Controller
{
    public function get(Request $request = null)
    {
        $programmes = Programme::select('code', 'id', 'ects', 'name')->get();
        return $programmes;
    }

    public function getTree(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $programme = Programme::select('code', 'id', 'name', 'ects')
            ->where('id', $validated['id'])
            ->firstOrFail();
        $programme->firstSemestre = new \stdClass();
        $programme->secondSemestre = new \stdClass();
        $programme->firstSemestre->UES = $this->getUEBySemester($validated['id'], 1);
        $programme->secondSemestre->UES = $this->getUEBySemester($validated['id'], 2);

        $programme->firstSemestre->countECTS = 40;
        $programme->secondSemestre->countECTS = 70;

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
