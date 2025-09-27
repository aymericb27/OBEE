<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageTerminaux;
use App\Models\AcquisApprentissageVise;
use App\Models\UniteEnseignement;
use Illuminate\Http\Request;

class treeController extends Controller
{


    public function getChildren(Request $request){
        $result = AcquisApprentissageVise::select('description as AAVDescription','id as AAVid', 'code as AAVCode')
        ->where('fk_AAT', $request->aatid)->get();
        foreach($result as $index => $aav){
            $result[$index]['ues'] = UniteEnseignement::select('unite_enseignement.id as UEid', 'unite_enseignement.name as UEname','ects as ueects', 'unite_enseignement.description as UEdescription')
            ->join('aavue','aavue.fk_unite_enseignement','=','unite_enseignement.id')
            ->where('aavue.fk_acquis_apprentissage_vise',$aav->AAVid)
            ->get();
        }
        return $result;
    }
}
