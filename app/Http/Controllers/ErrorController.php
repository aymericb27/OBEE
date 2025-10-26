<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;

class ErrorController extends Controller
{
    function searchErrorInAllUE()
    {
        $ues = UE::select("id", 'date_begin', 'code', 'name', 'date_end')->get();

        foreach ($ues as $ue) {
            $ue->prerequis = AAV::select('acquis_apprentissage_vise.id', 'code')
                ->join('aavue_prerequis', 'fk_acquis_apprentissage_prerequis', '=', 'acquis_apprentissage_vise.id')
                ->where('aavue_prerequis.fk_unite_enseignement', $ue->id)
                ->get();
            $ue->vise = AAV::select('acquis_apprentissage_vise.id', 'code')
                ->join('aavue_vise', 'fk_acquis_apprentissage_vise', '=', 'acquis_apprentissage_vise.id')
                ->where('aavue_vise.fk_unite_enseignement', $ue->id)
                ->get();
        }

        $isError = False;

        foreach ($ues as $ueA) {
            foreach ($ues as $ueB) {
                // On évite de comparer la même UE
                if ($ueA->id === $ueB->id) continue;

                // On récupère les IDs des AAV visés par B et prérequis de A
                $aavVisesB = $ueB->vise->pluck('id')->toArray();
                $aavPrerequisA = $ueA->prerequis->pluck('id')->toArray();

                // On cherche l’intersection entre les deux listes
                $intersection = array_intersect($aavVisesB, $aavPrerequisA);

                // S’il y a au moins un AAV commun, il faut vérifier les dates
                if (!empty($intersection)) {
                    if ($ueA->date_begin < $ueB->date_end) {
                        $isError = True;
                        $errorsHoraire[] = [
                            'ueA' => $ueA->code,
                            'ueB' => $ueB->code,
                            'message' => "L’UE {$ueA->code} ne peut pas commencer avant {$ueB->code} car elles partagent des acquis d'apprentissages dépendants.",
                            'aav_conflits' => $intersection,
                        ];
                    }
                }
            }
        }

        // Retourne la liste des conflits détectés
        return response()->json([
            'status' => empty($errors) ? 'ok' : 'error',
            'isError' => $isError,
            'errorsHoraire' => $errorsHoraire,
        ]);
    }
}
