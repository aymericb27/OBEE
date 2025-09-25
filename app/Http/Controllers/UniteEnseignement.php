<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise;
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

    public function getDetailed(Request $request){
        $validated = $request->validate([
            'ueid' => 'required|integer',
        ]);
        $response = UE::select('code as UECode', 'id as UEid', 'name as UEname','description as UEdescription')
        ->where('unite_enseignement.id',$validated['ueid'])
        ->first();

        $response->aavs = AcquisApprentissageVise::select('acquis_apprentissage_terminaux.code as AATCode','acquis_apprentissage_vise.description as AAVDescription', 'acquis_apprentissage_vise.code as AAVCode')
        ->leftJoin('aavue', 'aavue.fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
        ->leftJoin('acquis_apprentissage_terminaux', 'acquis_apprentissage_terminaux.id','=','acquis_apprentissage_vise.fk_AAT')
        ->where('aavue.fk_unite_enseignement',$validated['ueid'])
        ->get();
        return $response;
    }

    public function get(Request $request) {
        $withUE = $request->query('withUE');
        $ues = UE::select('id as UEid','name as UEname', 'ects','code as UEcode', 'description as UEdescription')->get();
        if(isset($withUE) && $withUE){
            $listUE = [];
            foreach($ues as $ue){
                $ue->ecs = UEEC::select('name as ECname', 'description as ECdescription', 'code as ECcode')
                ->leftJoin('element_constitutif', 'element_constitutif.id', '=', 'fk_element_constitutif')
                ->where('fk_unite_enseignement',$ue->UEid)->get();
                array_push($listUE,$ue);
            }
            return $listUE;
        } else {
            $ues = UE::get();

        }
        return $ues;
    }
}
