<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\ElementConstitutif as EC;
use App\Models\UEEC;
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

    public function getECs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $ecs = EC::select('element_constitutif.id', 'name', 'code')->join('ueec', 'ueec.fk_element_constitutif', '=', 'element_constitutif.id')
            ->where('fk_unite_enseignement', $validated['id'])->get();
        return $ecs;
    }

    public function getAAVs(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = AAV::select('acquis_apprentissage_vise.id', 'name', 'code')
            ->join('aavue', 'aavue.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
            ->where('aavue.fk_unite_enseignement', $validated['id'])
            ->get();

        return $response;
    }

    public function getDetailed(Request $request)
    {
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $response = UE::select('code', 'id', 'name', 'description')
            ->where('unite_enseignement.id', $validated['id'])
            ->first();
        return $response;
    }

    public function get(Request $request)
    {
        $withUE = $request->query('withUE');
        $ues = UE::select('id as UEid', 'name as UEname', 'ects', 'code as UEcode', 'description as UEdescription')->get();
        if (isset($withUE) && $withUE) {
            $listUE = [];
            foreach ($ues as $ue) {
                $ue->ecs = UEEC::select('name as ECname', 'description as ECdescription', 'code as ECcode')
                    ->leftJoin('element_constitutif', 'element_constitutif.id', '=', 'fk_element_constitutif')
                    ->where('fk_unite_enseignement', $ue->UEid)->get();
                array_push($listUE, $ue);
            }
            return $listUE;
        } else {
            $ues = UE::get();
        }
        return $ues;
    }
}
