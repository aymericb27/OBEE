<?php

namespace App\Http\Controllers;


class ExportController extends Controller
{

    public function exportUE($ueId)
    {
        $export = new \App\Exports\UEExport($ueId);
        return $export->download();
    }

    public function exportAAT($aatID)
    {
        $export = new \App\Exports\AATExport($aatID);
        return $export->download();
    }

    public function exportAAV($aavID)
    {
        $export = new \App\Exports\AAVExport($aavID);
        return $export->download();
    }

    public function exportPRO($proID)
    {
        $export = new \App\Exports\PROExport($proID);
        return $export->download();
    }

    /*     public function export(Request $request, string $type)
    {
        // On récupère les filtres envoyés par params
        $filters = $request->input('filter', []);
        $select = $request->input('select', []); // sécurité si jamais non défini

        // On choisit quel export exécuter selon le type
        switch (strtoupper($type)) {
            case 'UE':
                $export = new UEExport($filters, $select);
                $filename = 'unites_enseignements.xlsx';
                break;
            case 'PRO':
                $export = new ProgExport($filters);
                $filename = 'programmes.xlsx';
                break;

            case 'AAT':
                $export = new AATExport($filters);
                $filename = 'acquis_terminaux.xlsx';
                break;
            case 'AAV':
                $export = new AAVExport($filters);
                $filename = 'acquis_apprentissages.xlsx';
                break;
            default:
                return response()->json(['error' => 'Type d’export inconnu'], 400);
        }

        // Téléchargement via Laravel Excel
        return Excel::download($export, $filename);
    } */
}
