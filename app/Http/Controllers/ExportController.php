<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProgExport;
use App\Exports\UEExport;
use App\Exports\AATExport;
use App\Exports\AAVExport;

class ExportController extends Controller
{
    public function export(Request $request, string $type)
    {
        // On récupère les filtres envoyés par params
        $filters =$request->input('filter', []);
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
    }
}
