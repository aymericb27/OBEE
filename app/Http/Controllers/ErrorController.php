<?php

namespace App\Http\Controllers;

use App\Models\AcquisApprentissageVise as AAV;
use App\Models\Programme;
use App\Models\UniteEnseignement as UE;
use Illuminate\Http\Request;

class ErrorController extends Controller
{
    public function getUES()
    {
        $ues = UE::select("id", 'ects', 'code', 'name')->get();

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
        return $ues;
    }

    public function getSheduleError($intersection, $ueA, $ueB)
    {
        $errorsHoraire = null;
        // S’il y a au moins un AAV commun, il faut vérifier les dates
        if ($ueA->date_begin < $ueB->date_end) {
            $errorsHoraire = [
                'ueA' => $ueA,
                'ueB' => $ueB,
                'aav_conflits' => $intersection,
            ];
        }
        return $errorsHoraire;
    }

    public function getErrorUESShedule()
    {
        $ues = $this->getUES();
        $isError = False;
        $errorsHoraire = [];

        foreach ($ues as $ueA) {
            foreach ($ues as $ueB) {
                // On évite de comparer la même UE
                if ($ueA->id === $ueB->id) continue;

                // On récupère les IDs des AAV visés par B et prérequis de A
                $aavVisesB = $ueB->vise->pluck('id')->toArray();
                $aavPrerequisA = $ueA->prerequis->pluck('id')->toArray();
                // On cherche l’intersection entre les deux listes
                $intersection = array_intersect($aavVisesB, $aavPrerequisA);
                if (!empty($intersection)) {
                    $error  = $this->getSheduleError($intersection, $ueA, $ueB);
                    if ($error !== null) {
                        $error['aav'] = AAV::select('code', 'id', 'name')->whereIn('id', $error['aav_conflits'])
                            ->get();
                        $errorsHoraire[] = $error;
                    }
                }
            }
        }
        return response()->json([
            'status' => empty($errors) ? 'ok' : 'error',
            'isError' => $isError,
            'errorsHoraire' => $errorsHoraire,
        ]);
    }

    public function getErrorProEctsNumber()
    {
        $progController = new ProgrammeController();
        $progs = $progController->get();

        foreach ($progs as $prog) {
            $prog->UEECts = (int) UE::join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('ue_programme.fk_programme', $prog->id)
                ->sum('unite_enseignement.ects');
            if ($prog->UEECts !== $prog->ects) {
                $prog->ues = $prog->load('ues');
                $errorsECTS[] = $prog;
            }
        }
        return response()->json($errorsECTS);
    }

    public function getErrorUE(Request $request)
    {
        return 1;
        $validated = $request->validate([
            'id' => 'required|integer',
        ]);
        $errorsHoraire = [];
        $errorsECTS = [];
        $isError = false;
        $ueA = UE::where('id', $validated['id'])->first();
        $ues = !empty($ues) ? $ues : $this->getUES();
        $progs = Programme::join('ue_programme', 'programme.id', '=', 'ue_programme.fk_programme')
            ->where('ue_programme.fk_unite_enseignement', $ueA->id)
            ->get();
        foreach ($progs as $prog) {
            $prog->UEECts = UE::join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('ue_programme.fk_programme', $prog->id)
                ->sum('unite_enseignement.ects');
            if ($prog->UEECts !== $prog->ects) {
                $isError = true;
                $errorsECTS[] = $prog;
            }
        }
        foreach ($ues as $ueB) {
            // On évite de comparer la même UE
            if ($ueA->id === $ueB->id) continue;

            // On récupère les IDs des AAV visés par B et prérequis de A
            $aavVisesB = $ueB->aavvise->pluck('id')->toArray();
            $aavPrerequisA = $ueA->prerequis->pluck('id')->toArray();
            // On cherche l’intersection entre les deux listes
            $intersection = array_intersect($aavVisesB, $aavPrerequisA);
            if (!empty($intersection)) {
                $error  = $this->getSheduleError($intersection, $ueA, $ueB);
                if ($error !== null) {
                    $ueA->error = true;
                    $ueB->error = true;
                    $error['aav'] = AAV::select('code', 'id', 'name')->whereIn('id', $error['aav_conflits'])
                        ->get();
                    $isError = true;
                    $errorsHoraire[] = $error;
                }
            }
        }
        // Retourne la liste des conflits détectés
        return response()->json([
            'status' => empty($errors) ? 'ok' : 'error',
            'isError' => $isError,
            'errorsShedule' => $errorsHoraire,
            'errorsECTS' => $errorsECTS,
        ]);
    }


    public function getErrorUES($ues, $returnList = False)
    {
        return false;
        $ues = !empty($ues) ? $ues : $this->getUES();

        $isError = False;
        $errorsHoraire = [];
        $errorsECTS = [];
        $progController = new ProgrammeController();
        $progs = $progController->get();

        foreach ($progs as $prog) {
            $prog->UEECts = UE::join('ue_programme', 'ue_programme.fk_unite_enseignement', '=', 'unite_enseignement.id')
                ->where('ue_programme.fk_programme', $prog->id)
                ->sum('unite_enseignement.ects');
            if ($prog->UEECts !== $prog->ects) {
                $isError = true;
                $errorsECTS[] = ['id' => $prog->id, 'UEECts' => $prog->UEECts];
            }
        }
        foreach ($ues as $ueA) {
            foreach ($ues as $ueB) {
                // On évite de comparer la même UE
                if ($ueA->id === $ueB->id) continue;

                // On récupère les IDs des AAV visés par B et prérequis de A
                $aavVisesB = $ueB->vise->pluck('id')->toArray();
                $aavPrerequisA = $ueA->prerequis->pluck('id')->toArray();
                // On cherche l’intersection entre les deux listes
                $intersection = array_intersect($aavVisesB, $aavPrerequisA);
                if (!empty($intersection)) {
                    $error  = $this->getSheduleError($intersection, $ueA, $ueB);
                    if ($error !== null) {
                        $ueA->error = true;
                        $ueB->error = true;
                        $isError = true;
                        $errorsHoraire[] = $error;
                    }
                }
            }
            // on récupère les crédits selon le programme

        }
        if ($returnList) {
            return $ues;
        } else {
            // Retourne la liste des conflits détectés
            return response()->json([
                'status' => empty($errors) ? 'ok' : 'error',
                'isError' => $isError,
                'errorsHoraire' => $errorsHoraire,
                'errorECTS' => $errorsECTS,
            ]);
        }
    }
}
